<?php
header('Content-type:text/html; charset=utf-8');
session_start();

// if( $_SESSION['logic_erp']['user_id'] == "" ) header("location:login.php");
// $user_id = $_SESSION['logic_erp']['user_id'];

require_once('../../../includes/common.php');

$data=$_REQUEST['data'];
$action=$_REQUEST['action']; 

if ($action=="load_drop_down_floor")
{ 
	echo create_drop_down( "cbo_floor_id", "", "select id,floor_name from lib_prod_floor where status_active =1 and is_deleted=0 and company_id='$data' and production_process=5 order by floor_name","id,floor_name", 1, "-- Select --", $selected, "",0 ); 
	
	exit();    	 
}

if ($action=="load_drop_down_buyer")
{
	echo create_drop_down( "cbo_buyer_name", "", "SELECT buy.id,buy.buyer_name from lib_buyer buy, lib_buyer_tag_company b where buy.status_active =1 and buy.is_deleted=0 and b.buyer_id=buy.id and b.tag_company='$data' $buyer_cond and buy.id in (select  buyer_id from  lib_buyer_party_type where party_type in (1,3,21,90)) order by buyer_name","id,buyer_name", 1, "-- Select --", $selected, "" );     	 
	exit(); 
}

if ($action=="show_floor_listview")
{
	$sql = "select id,floor_name from lib_prod_floor where status_active =1 and is_deleted=0 and company_id='$data' and production_process=5 order by floor_name";
	$res = sql_select($sql);
	$i=1;
	foreach ($res as $val) 
	{
		?>
		<div class="inputGroup">
		    <input id="option_<?=$i;?>" name="floor_name[]" value="<?=$val['ID'];?>"  type="checkbox"/>
		    <label for="option_<?=$i;?>"><?=$val['FLOOR_NAME'];?></label>
		 </div>
		 <!-- onclick="get_sewing_line()" -->
		<?
		$i++;
	}
}


if($action=="load_drop_down_sewing_line_floor")
{
	$explode_data = explode("_",$data);
	$txt_sewing_date = $explode_data[2];
	$wo_company_id = $explode_data[1];
	$cond="";
	$prod_reso_allo=return_field_value("auto_update","variable_settings_production","company_name =$wo_company_id and variable_list=23 and is_deleted=0 and status_active=1");
	if($prod_reso_allo==1)
	{
		$line_library=return_library_array( "select id,line_name from lib_sewing_line", "id", "line_name"  );
		$line_array=array();
		if($txt_sewing_date=="")
		{
			if( $explode_data[0] ) $cond.= " and floor_id in($explode_data[0])";

			$line_data=sql_select("select id, line_number from prod_resource_mst where is_deleted=0 $cond");
		}
		else
		{
			if( $explode_data[0]) $cond.= " and a.floor_id in($explode_data[0])";

			if($db_type==0)
			{
				$line_data=sql_select("SELECT a.id, a.line_number from prod_resource_mst a, prod_resource_dtls b where a.id=b.mst_id and b.pr_date='".change_date_format($txt_sewing_date,'yyyy-mm-dd')."' and a.is_deleted=0 and b.is_deleted=0 $cond group by a.id  order by a.id, a.prod_resource_num");
			}
			else if($db_type==2 || $db_type==1)
			{
				$line_data=sql_select("SELECT a.id, a.line_number,a.prod_resource_num from prod_resource_mst a, prod_resource_dtls b where a.id=b.mst_id and b.pr_date='".date("j-M-Y",strtotime($txt_sewing_date))."' and a.is_deleted=0 and b.is_deleted=0 $cond group by a.id,a.prod_resource_num, a.line_number  order by a.id, a.prod_resource_num");
			}
		}
		$line_merge=9999; 
		foreach($line_data as $row)
		{
			$line='';
			$line_number=explode(",",$row[csf('line_number')]);
			foreach($line_number as $val)
			{
				if(count($line_number)>1)
				{
					$line_merge++;
					$new_arr[$line_merge]=$row[csf('id')];
				}
				else
					$new_arr[$val]=$row[csf('id')];
				if($line=='') $line=$line_library[$val]; else $line.=",".$line_library[$val];
			}
			$line_array[$row[csf('id')]]=$line;
		}

		ksort($new_arr);
		foreach($new_arr as $key=>$v)
		{
			$line_array_new[$v]=$line_array[$v];
		}
		//echo create_drop_down( "cbo_sewing_line", 110,$line_array_new,"", 1, "--- Select ---", $selected, "",0,0 );
		echo create_drop_down( "cbo_line_id", "",$line_array_new,"", 1, "--- Select Line ---", $selected, "",0,0 );
	}
	else
	{
		if( $explode_data[0]!=0 ) $cond = " and floor_name in($explode_data[0])";

		echo create_drop_down( "cbo_line_id", "", "select id,line_name from lib_sewing_line where  is_deleted=0 and status_active=1 and floor_name!=0 $cond order by id, line_name","id,line_name", 1, "--- Select Line---", $selected, "",0,0 );
	}
	exit();
}

if ($action == "eval_multi_select") {
    echo "set_multiselect('cbo_line_id','0','0','','0');\n";
    exit();
}



if($action=="line_search_popup")
{		  
	echo load_html_head_contents("Popup Info","../../../", 1, 1,$unicode,1);
	?>
	<script>
		var selected_id = new Array;
		var selected_name = new Array;
		
    	function check_all_data() {

			var tbl_row_count = document.getElementById( 'list_view' ).rows.length;
			tbl_row_count = tbl_row_count - 0;
			for( var i = 1; i <= tbl_row_count; i++ )
			 {
				var onclickString = $('#tr_' + i).attr('onclick');
				var paramArr = onclickString.split("'");
				var functionParam = paramArr[1];
				js_set_value( functionParam );
			 }
		}
		
		function toggle( x, origColor ) {
			var newColor = 'yellow';
			if ( x.style ) { 
				x.style.backgroundColor = ( newColor == x.style.backgroundColor )? origColor : newColor;
			}
		}
		
		function js_set_value( strCon ) 
		{
			//alert(strCon)
			var splitSTR = strCon.split("_");
			var str = splitSTR[0];
			var selectID = splitSTR[1];
			var selectDESC = splitSTR[2];
			//$('#txt_individual_id' + str).val(splitSTR[1]);
			//$('#txt_individual' + str).val('"'+splitSTR[2]+'"');
			
			toggle( document.getElementById( 'tr_' + str ), '#FFFFCC' );
			
			if( jQuery.inArray( selectID, selected_id ) == -1 ) {
				selected_id.push( selectID );
				selected_name.push( selectDESC );					
			}
			else {
				for( var i = 0; i < selected_id.length; i++ ) {
					if( selected_id[i] == selectID ) break;
				}
				selected_id.splice( i, 1 );
				selected_name.splice( i, 1 ); 
			}
			var id = ''; var name = ''; var job = '';
			for( var i = 0; i < selected_id.length; i++ ) {
				id += selected_id[i] + ',';
				name += selected_name[i] + ','; 
			}
			id 		= id.substr( 0, id.length - 1 );
			name 	= name.substr( 0, name.length - 1 ); 
			
			$('#txt_selected_id').val( id );
			$('#txt_selected').val( name ); 
		}
		
		function set_all_data() {

			var tbl_row_count = document.getElementById( 'list_view' ).rows.length;
			tbl_row_count = tbl_row_count - 0;
			for( var i = 1; i <= tbl_row_count; i++ )
			 {
				 
				if(($('#hidden_old_id' + i).val()*1)==1)
				{ 
					var onclickString = $('#tr_' + i).attr('onclick');
					var paramArr = onclickString.split("'");
					var functionParam = paramArr[1];
					js_set_value( functionParam );
				}
			 }
		}
		
		
		function fn_onClosed()
		{
			parent.emailwindow.hide();
		}
    </script>
	<?
	extract($_REQUEST);
	//echo $company;die;
	$line_library=return_library_array( "SELECT id,line_name from lib_sewing_line", "id", "line_name");
	if($wo_company_name==0) $company_name=""; else $company_name=" and b.company_name in($wo_company_name)";//job_no

		$line_array=array();
		if($date_from=="")
		{
			$data_format="";
		}
		else
		{
			if($db_type==0)	$data_format=" and b.pr_date='".change_date_format($date_from,'yyyy-mm-dd')."'";
			if($db_type==2)	$data_format=" and b.pr_date='".change_date_format($date_from,'','',1)."'";
		}
		// if( $location_name!=0 ) $cond .= " and a.location_id in($location_name)";
		if( $floor_name!="" ) $cond.= " and a.floor_id in($floor_name)";
		
		$line_sql="SELECT a.id, a.line_number from prod_resource_mst a, prod_resource_dtls b where a.id=b.mst_id $data_format and a.is_deleted=0 and b.is_deleted=0 $cond group by a.id, a.line_number order by a.id";
		// echo $line_sql;
		$line_sql_result=sql_select($line_sql);
		
		?>
            <input type='hidden' id='txt_selected_id' />
            <input type='hidden' id='txt_selected' />
            <table cellspacing="0" width="230"  border="1" rules="all" class="rpt_table" >
            	<thead>
                	<th width="30"></th>
                    <th width="200">Line Name</th>
                </thead>
            </table>
            <div style="width:250px; max-height:300px; overflow-y:auto;" id="scroll_body" >          
        		<table cellspacing="0" width="230"  border="1" rules="all" class="rpt_table" id="list_view" >
                <? 
				
				$i=1;
				$previous_line_arr=explode(",",$line_id);
				 foreach($line_sql_result as $row)
				 {
        			 $bgcolor=($i%2==0)?"#E9F3FF":"#FFFFFF";
					 $flag=0;
					 if(in_array($row[csf('id')],$previous_line_arr))
					 {
						 $flag=1;
					 }
        
					$line_val='';
					$line_id=explode(",",$row[csf('line_number')]);
					foreach($line_id as $line_id)
					{
						if($line_val=="") $line_val=$line_library[$line_id]; else $line_val.=','.$line_library[$line_id];
					}
					?>
                	<tr bgcolor="<? echo $bgcolor ; ?>" id="tr_<? echo $i; ?>" onClick="js_set_value('<? echo $i.'_'.$row[csf('id')].'_'.$line_val; ?>')" style="cursor:pointer;">
                    	<td width="30"><? echo $i;?></td>
                        <td width="200">
						<? echo $line_val;?>
                        <input type="hidden" id="hidden_old_id<? echo $i; ?>" name="hidden_old_id<? echo $i; ?>" value="<?php echo $flag; ?>" />
                        </td>
                    </tr>
                 <?
				 $i++;
				 }
				 ?>
              </table>
           </div>
        <table width="230">
            <tr align="center">
                <td>
            		<div align="left" style="width:50%; float:left">
                        <input id="check_all" type="checkbox" onclick="check_all_data()" name="check_all">
                            Check / Uncheck All
                    </div>
                    <div align="left" style="width:50%; float:left">
                        <input type="button" name="btn_close" class="formbutton" style="width:100px" value="Close" onClick="fn_onClosed()" />
                    </div>
               </td>
            </tr>
        </table>
         <script>
			set_all_data();
			setFilterGrid("list_view",-1);
		</script>
        <?

	exit();
}

if($action=="report_generate")
{
	extract($_REQUEST);
	$process = array( &$_POST );
	
	//change_date_format($txt_date);die;
	
	if($db_type==0)	$txt_date=change_date_format($txt_date,'yyyy-mm-dd');
	if($db_type==2)	$txt_date=change_date_format($txt_date,'M-d-Y','',1);
	$txt_date="'".$txt_date."'";
	
	// extract(check_magic_quote_gpc( $process ));
	$companyArr = return_library_array("select id,company_name from lib_company","id","company_name"); 
	$buyerArr = return_library_array("select id,buyer_name from lib_buyer","id","buyer_name"); 
	$colorArr = return_library_array("select id,color_name from lib_color","id","color_name"); 
	$countryArr = return_library_array("select id,country_name from lib_country","id","country_name"); 
	$locationArr = return_library_array("select id,location_name from lib_location","id","location_name"); 
	$floorArr = return_library_array("select id,floor_name from lib_prod_floor","id","floor_name"); 
	$lineArr = return_library_array("select id,line_name from lib_sewing_line where company_name=$company_id","id","line_name"); 
	$prod_reso_arr=return_library_array( "select id, line_number from prod_resource_mst",'id','line_number');

	$company_id=str_replace("'","",$cbo_company_name);
	//**********************************************************************************************
	$lineDataArr = sql_select("SELECT id, line_name, sewing_line_serial from lib_sewing_line where is_deleted=0 and status_active=1 and company_name=$company_id order by sewing_line_serial"); 
	foreach($lineDataArr as $lRow)
	{
		$lineArr[$lRow[csf('id')]]=$lRow[csf('line_name')];
		$lineSerialArr[$lRow[csf('id')]]=$lRow[csf('sewing_line_serial')];
		$lastSlNo=$lRow[csf('sewing_line_serial')];
	}
	
	
	if($db_type==0)
	{
		$min_shif_start=return_field_value("min(TIME_FORMAT(d.prod_start_time, '%H:%i' ))  as line_start_time","prod_resource_mst a,prod_resource_dtls  b,prod_resource_dtls_time d ","a.id=d.mst_id and a.id=b.mst_id and  a.company_id=$company_id and shift_id=1 and pr_date=$txt_date and a.is_deleted=0 and b.is_deleted=0 and d.is_deleted=0","line_start_time");	
	}
	else
	{
		$min_shif_start=return_field_value("min(TO_CHAR(d.prod_start_time,'HH24:MI')) as line_start_time","prod_resource_mst a,prod_resource_dtls b,prod_resource_dtls_time d ","a.id=d.mst_id and a.id=b.mst_id and  a.company_id=$company_id and shift_id=1 and pr_date=$txt_date and a.is_deleted=0 and b.is_deleted=0 and d.is_deleted=0","line_start_time");
	}
	// echo $min_shif_start;
	if($min_shif_start=="")
	{
		echo "<p style='font-size:20px;color:#ffffff;', align='center'>No Line Engage for the selected Date.Please Check Actual Production Resource Entry.<p/>";
		disconnect($con);
		die;		
	}
	
	
	/*===================================================================================== /
	/										shift time 										/
	/===================================================================================== */

	$start_time_arr=array();
	if($db_type==0)
	{
		$start_time_data_arr=sql_select("select company_name, shift_id, TIME_FORMAT( prod_start_time, '%H:%i' ) as prod_start_time,TIME_FORMAT( lunch_start_time, '%H:%i' ) as lunch_start_time from variable_settings_production where company_name in($company_id) and shift_id=1  and variable_list=26 and status_active=1 and is_deleted=0");
	}
	else
	{
		$start_time_data_arr=sql_select("select company_name, shift_id, TO_CHAR(prod_start_time,'HH24:MI') as prod_start_time,TO_CHAR(lunch_start_time,'HH24:MI') as lunch_start_time from variable_settings_production where  company_name in($company_id) and  shift_id=1 and variable_list=26 and status_active=1 and is_deleted=0");	
	}
	// print_r($start_time_data_arr);
	$lunch_start_time = "";
	foreach($start_time_data_arr as $row)
	{
		$start_time_arr[$row[csf('shift_id')]]['pst']=$row[csf('prod_start_time')];
		$start_time_arr[$row[csf('shift_id')]]['lst']=$row[csf('lunch_start_time')];
		$exp = explode(":",$row[csf('lunch_start_time')]);
		$lunch_start_time = $exp[0]*1;
	}
	// echo $lunch_start_time;die;
	$prod_start_hour=$start_time_arr[1]['pst'];
	$global_start_lanch=$start_time_arr[1]['lst'];
	if($prod_start_hour=="") $prod_start_hour="08:00";
	$start_time=explode(":",$prod_start_hour);
	// $hour=substr($start_time[0],1,1); 
	$hour = $start_time[0]*1;
	$minutes=$start_time[1]; 
	$last_hour=23;
	$lineWiseProd_arr=array(); $prod_arr=array(); $start_hour_arr=array();
	$start_hour=$prod_start_hour;
	$start_hour_arr[$hour]=$start_hour;
	for($j=$hour;$j<$last_hour;$j++)
	{
		$start_hour=add_time($start_hour,60);
		$start_hour_arr[$j+1]=substr($start_hour,0,5);
	}
	//echo $prod_start_hour;die;
	$start_hour_arr[$j+1]='23:59';
	if($prod_start_hour>$min_shif_start)  $prod_start_hour=$min_shif_start;
	$actual_date=date("Y-m-d");
	$actual_production_date=date("Y-m-d",strtotime(str_replace("'","",$txt_date)));
	$actual_time=substr(date("Y-m-d H:i:s",strtotime($pc_date_time)),11,2);	
	$acturl_hour_minute=date("H:i",strtotime($pc_date_time));	
	$generated_hourarr=array();
	$first_hour_time=explode(":",$min_shif_start);
	$hour_line=substr($first_hour_time[0],1,1); $minutes_one=$start_time[1];
	$line_start_hour_arr[$hour_line]=$min_shif_start;
	//echo $prod_start_hour;die;
	for($l=$hour_line;$l<$last_hour;$l++)
	{
		$min_shif_start=add_time($min_shif_start,60);
		$line_start_hour_arr[$l+1]=substr($min_shif_start,0,5);
	}
	
	$line_start_hour_arr[$j+1]='23:59';

	// echo "<pre>";print_r($start_hour_arr);echo "</pre>";

	/*===================================================================================== /
	/								get actual resource variable							/
	/===================================================================================== */
	$prod_reso_allo=return_field_value("auto_update","variable_settings_production","company_name=$company_id and variable_list=23 and is_deleted=0 
	and status_active=1");

	/*===================================================================================== /
	/										query condition									/
	/===================================================================================== */
		
	//if(str_replace("'","",$company_id)==0) $company_name=""; else $company_name="and a.company_id=".str_replace("'","",$company_id)."";
	if(str_replace("'","",$company_id)==0) $company_name=""; else $company_name="and a.serving_company=".str_replace("'","",$company_id)."";
	if(str_replace("'","",$company_id)==0) $company_name_subcon=""; else $company_name_subcon="and a.company_id=".str_replace("'","",$company_id)."";
	
	if(str_replace("'","",$cbo_location_id)==0) 
	{
		$subcon_location="";
		$location="";
	}
	else 
	{
		$location=" and a.location=".str_replace("'","",$cbo_location_id)."";
		$subcon_location=" and a.location_id=".str_replace("'","",$cbo_location_id)."";
	}
	$cbo_floor_id=str_replace("'","",$cbo_floor_id);
	if($cbo_floor_id==0) $floor=""; else $floor="and a.floor_id in(".$cbo_floor_id.")";
    if(str_replace("'","",$hidden_line_id)==0)
	{ 
		$line=""; 
		$subcon_line="";
	}
	else 
	{
		$subcon_line="and a.line_id in(".str_replace("'","",$hidden_line_id).")";
		$line="and a.sewing_line in(".str_replace("'","",$hidden_line_id).")";
	}
	
	if(str_replace("'","",trim($txt_date))=="") $txt_date_from=""; else $txt_date_from=" and a.production_date=$txt_date";
	/*===================================================================================== /
	/										prod resource data								/location_id
	/===================================================================================== */	
	// echo $prod_reso_allo[0]; die;
	$prod_res_cond = "";
	// $prod_res_cond .= (str_replace("'", "", $cbo_location_id)==0) ? "" : " and a.=$cbo_location_id";
	$prod_res_cond .= (str_replace("'", "", $cbo_floor_id)==0) ? "" : " and a.floor_id in($cbo_floor_id)";
	$prod_res_cond .= (str_replace("'", "", $hidden_line_id)==0) ? "" : " and a.id in(".str_replace("'", "", $hidden_line_id).")";
	

	// $production_serial_arr=array();
	if($prod_reso_allo[0]==1)
	{
		$prod_resource_array=array();
		$prod_resource_array2=array();

		$dataArray_sql=sql_select("SELECT a.id, a.location_id, a.floor_id, a.line_number, b.active_machine, b.pr_date, b.man_power, b.operator, b.helper,b.smv_adjust,b.smv_adjust_type, b.line_chief, b.target_per_hour, b.working_hour,c.from_date,c.to_date,c.capacity from prod_resource_mst a, prod_resource_dtls b,prod_resource_dtls_mast c where a.id=c.mst_id and c.id=b.mast_dtl_id and a.company_id=$company_id and b.pr_date=$txt_date and a.is_deleted=0 and b.is_deleted=0 and c.is_deleted=0 $prod_res_cond");
		
		foreach($dataArray_sql as $val)
		{
			$prod_resource_array[$val[csf('id')]]['man_power']=$val[csf('man_power')];
			$prod_resource_array[$val[csf('id')]]['operator']=$val[csf('operator')];
			$prod_resource_array[$val[csf('id')]]['helper']=$val[csf('helper')];
			$prod_resource_array[$val[csf('id')]]['terget_hour']=$val[csf('target_per_hour')];
			$prod_resource_array[$val[csf('id')]]['working_hour']=$val[csf('working_hour')];
			$prod_resource_array[$val[csf('id')]]['tpd']=$val[csf('target_per_hour')]*$val[csf('working_hour')];
			$prod_resource_array[$val[csf('id')]]['day_start']=$val[csf('from_date')];
			$prod_resource_array[$val[csf('id')]]['day_end']=$val[csf('to_date')];
			$prod_resource_array[$val[csf('id')]]['capacity']=$val[csf('capacity')];
			$prod_resource_array[$val[csf('id')]]['smv_adjust']=$val[csf('smv_adjust')];
			$prod_resource_array[$val[csf('id')]]['smv_adjust_type']=$val[csf('smv_adjust_type')];

		}
		// echo "<pre>";print_r($prod_resource_array);die();

		// ============================= from style popup ==============================
		$sql="SELECT a.id, a.location_id, a.floor_id, a.line_number, b.active_machine, b.pr_date, b.man_power,b.smv_adjust,b.smv_adjust_type, b.line_chief, b.target_per_hour, c.from_date,c.to_date,c.capacity,d.po_id,d.target_per_line,d.operator, d.helper,d.working_hour,d.actual_smv,d.gmts_item_id from prod_resource_mst a, prod_resource_dtls b,prod_resource_dtls_mast c,prod_resource_color_size d where a.id=c.mst_id and c.id=b.mast_dtl_id and d.mst_id=a.id and d.dtls_id=c.id and a.company_id=$company_id and b.pr_date=$txt_date and a.is_deleted=0 and b.is_deleted=0 and c.is_deleted=0 $prod_res_cond";
		// echo $sql;die();
		$sql_res=sql_select($sql);
		foreach($sql_res as $val)
		{			
			$prod_resource_array2[$val[csf('id')]]['man_power']+=$val[csf('operator')]+$val[csf('helper')];
			$prod_resource_array2[$val[csf('id')]]['operator']+=$val[csf('operator')];
			$prod_resource_array2[$val[csf('id')]]['helper']+=$val[csf('helper')];
			$prod_resource_array2[$val[csf('id')]]['terget_hour']+=$val[csf('target_per_line')];
			$prod_resource_array2[$val[csf('id')]]['working_hour']+=$val[csf('working_hour')];
			$prod_resource_array2[$val[csf('id')]]['tpd']+=$val[csf('target_per_line')]*$val[csf('working_hour')];

			$prod_resource_smv_array[$val[csf('id')]][$val[csf('po_id')]][$val[csf('gmts_item_id')]]=$val[csf('actual_smv')];	
		}
		// echo "<pre>";print_r($prod_resource_array2);die();

	}
	
	// print_r($extra_minute_arr);die;
	
 	//*********************************************************************
  	if($db_type==0)
	{
		$manufacturing_company=return_field_value("group_concat(comp.id) as company_id","lib_company as comp", "comp.core_business=1 and comp.status_active=1 and comp.is_deleted=0 and id=$company_id","company_id");
	}
	else
	{
		$manufacturing_company=return_field_value("listagg(comp.id,',') within group (order by comp.id) as company_id","lib_company comp", "comp.core_business=1 and comp.status_active=1 and comp.is_deleted=0 and id=$company_id","company_id");
	}
	// echo $manufacturing_company;die();
	
	if($db_type==0) $prod_start_cond="prod_start_time";
	else if($db_type==2) $prod_start_cond="TO_CHAR(prod_start_time,'DD-MON-YYYY HH24:MI')";
	
	$variable_start_time_arr='';
	$prod_start_time=sql_select("select $prod_start_cond as prod_start_time from variable_settings_production where company_name=$company_id and variable_list=26 and status_active=1 and is_deleted=0 and shift_id=1");
	//echo "select company_name, prod_start_time from variable_settings_production where company_name=$company_id and variable_list=26 and status_active=1 and is_deleted=0 and shift_id=1";
	foreach($prod_start_time as $row)
	{
		$ex_time=explode(" ",$row[csf('prod_start_time')]);
		if($db_type==0) $variable_start_time_arr=$row[csf('prod_start_time')];
		else if($db_type==2) $variable_start_time_arr=$ex_time[1];
	}//die;
	//echo $variable_start_time_arr;
	unset($prod_start_time);
	$current_date_time=date('d-m-Y H:i');
	$variable_date=change_date_format(str_replace("'","",$txt_date)).' '.$variable_start_time_arr;
	//echo $variable_date.'='.$current_date_time;
	$datediff=datediff("n",$variable_date,$current_date_time);
	
	$ex_date_time=explode(" ",$current_date_time);
	$current_date=$ex_date_time[0];
	$current_time=$ex_date_time[1];
	$ex_time=explode(":",$current_time);
	
	$search_prod_date=change_date_format(str_replace("'","",$txt_date));
	$current_eff_min=($ex_time[0]*60)+$ex_time[1];
	//echo $current_date.'='.$search_prod_date;
	$variable_time= explode(":",$variable_start_time_arr);
	$vari_min=($variable_time[0]*60)+$variable_time[1];
	$difa_time=explode(".",number_format(($current_eff_min-$vari_min)/60,2));//datediff("",$ctime,$variable_start_time_arr);
	$dif_time=number_format($datediff/60,2);
	$dif_hour_min=date("H", strtotime($dif_time));

	
   
	if($db_type==2)
	{
		$pr_date=str_replace("'","",$txt_date);
		$pr_date_old=explode("-",str_replace("'","",$txt_date));
		$month=strtoupper($pr_date_old[1]);
		$year=substr($pr_date_old[2],2);
		$pr_date=$pr_date_old[0]."-".$month."-".$year;
	}
	if($db_type==0)
	{
		$pr_date=str_replace("'","",$txt_date);
	}
	
	$i=1; $grand_total_good=0; $grand_alter_good=0; $grand_total_reject=0;
	$html="";
	$floor_html="";
    $check_arr=array();
	/*===================================================================================== /
	/										get production data								/
	/===================================================================================== */
	// echo "source=".str_replace("'","",$cbo_source);die;
	if(str_replace("'","",$cbo_source)==2)
	{
		$sql="SELECT  a.company_id, a.location_id as location, a.floor_id, a.prod_reso_allo, a.production_date, a.LINE_ID as sewing_line,b.PARTY_ID as buyer_name,c.CUST_STYLE_REF as style_ref_no,b.JOB_NO_PREFIX_NUM,b.SUBCON_JOB as job_no, a.ORDER_ID as po_break_down_id, a.GMTS_ITEM_ID as item_number_id,c.ORDER_NO as po_number,a.remarks,sum(d.PROD_QNTY) as good_qnty,TO_CHAR(a.HOUR,'HH24:MI') as prod_hour,TO_CHAR(a.HOUR,'HH24') as last_prod_hour,"; 
		$first=1;
		for($h=$hour;$h<$last_hour;$h++)
		{
			$bg=$start_hour_arr[$h];
			$end=substr(add_time($start_hour_arr[$h],60),0,5);
			$prod_hour="prod_hour".substr($bg,0,2);
			if($first==1)
			{
				$sql.="sum(CASE WHEN  TO_CHAR(a.HOUR,'HH24:MI')<='$end' and a.production_type=2 THEN PROD_QNTY else 0 END) AS $prod_hour,";
			}
			else
			{
				$sql.="sum(CASE WHEN TO_CHAR(a.HOUR,'HH24:MI')>'$bg' and TO_CHAR(a.HOUR,'HH24:MI')<='$end' and a.production_type=2 
				THEN PROD_QNTY else 0 END) AS $prod_hour,";
			}
			$first++;
		}
		$sql.="sum(CASE WHEN TO_CHAR(a.HOUR,'HH24:MI')>'$start_hour_arr[$last_hour]' and TO_CHAR(a.HOUR,'HH24:MI')<='$start_hour_arr[24]' and a.production_type=2 THEN PROD_QNTY else 0 END) AS prod_hour233 
		FROM  SUBCON_GMTS_PROD_DTLS a ,SUBCON_GMTS_PROD_COL_SZ d,SUBCON_ORD_BREAKDOWN e, SUBCON_ORD_DTLS c,SUBCON_ORD_MST b
		WHERE a.id=d.DTLS_ID and d.ORD_COLOR_SIZE_ID=e.id and e.ORDER_ID=c.id and c.mst_id=b.id
		and a.production_type=2 and a.status_active=1 and a.is_deleted=0 and b.is_deleted=0 and c.is_deleted=0 and d.status_active=1 and d.is_deleted=0 and e.status_active=1 and e.is_deleted=0 $company_name_subcon $location $floor $subcon_line $buyer_id_cond  $txt_date_from 
		GROUP BY b.SUBCON_JOB,b.job_no_prefix_num, a.company_id, a.location_id, a.floor_id,a.ORDER_ID,a.prod_reso_allo, a.production_date, a.LINE_ID,b.PARTY_ID,c.CUST_STYLE_REF,a.GMTS_ITEM_ID,c.ORDER_NO,a.remarks,a.HOUR";
	}
	else
	{
		$sql="SELECT  a.company_id, a.location, a.floor_id, a.prod_reso_allo, a.production_date, a.sewing_line,b.buyer_name  as buyer_name,b.style_ref_no,b.job_no_prefix_num,b.job_no, a.po_break_down_id, a.item_number_id,c.po_number as po_number,c.file_no,c.unit_price,c.grouping as ref,c.shipment_date,e.color_number_id,e.country_id, d.color_type_id,a.remarks,sum(d.production_qnty) as good_qnty,TO_CHAR(a.production_hour,'HH24:MI') as prod_hour,TO_CHAR(a.production_hour,'HH24') as last_prod_hour,"; 
		$first=1;
		for($h=$hour;$h<$last_hour;$h++)
		{
			$bg=$start_hour_arr[$h];
			$end=substr(add_time($start_hour_arr[$h],60),0,5);
			$prod_hour="prod_hour".substr($bg,0,2);
			if($first==1)
			{
				$sql.="sum(CASE WHEN  TO_CHAR(a.production_hour,'HH24:MI')<='$end' and a.production_type=5 THEN production_qnty else 0 END) AS $prod_hour,";
			}
			else
			{
				$sql.="sum(CASE WHEN TO_CHAR(a.production_hour,'HH24:MI')>'$bg' and TO_CHAR(a.production_hour,'HH24:MI')<='$end' and a.production_type=5 
				THEN production_qnty else 0 END) AS $prod_hour,";
			}
			$first++;
		}
		$sql.="sum(CASE WHEN TO_CHAR(a.production_hour,'HH24:MI')>'$start_hour_arr[$last_hour]' and TO_CHAR(a.production_hour,'HH24:MI')<='$start_hour_arr[24]' and a.production_type=5 THEN production_qnty else 0 END) AS prod_hour233 
		FROM  pro_garments_production_mst a ,pro_garments_production_dtls d,wo_po_color_size_breakdown e, wo_po_break_down c,wo_po_details_master b
		WHERE a.id=d.mst_id and d.color_size_break_down_id=e.id and e.po_break_down_id=c.id and c.job_id=b.id
		and a.production_type=5 and a.status_active=1 and a.is_deleted=0 and b.is_deleted=0 and c.is_deleted=0 and d.status_active=1 and d.is_deleted=0 and e.status_active=1 and e.is_deleted=0 $company_name $location $floor $line $buyer_id_cond  $txt_date_from 
		GROUP BY b.job_no,b.job_no_prefix_num, a.company_id, a.location, a.floor_id,a.po_break_down_id,a.prod_reso_allo, a.production_date, a.sewing_line,b.buyer_name,b.style_ref_no,a.item_number_id,c.po_number,c.unit_price,c.file_no,c.grouping ,d.color_type_id,a.remarks,a.production_hour,c.shipment_date,e.color_number_id,e.country_id";//ORDER BY a.location,a.floor_id,a.sewing_line
	}
	// echo $sql;die;
	// echo $hour."==".$last_hour."";die;
	$sql_resqlt=sql_select($sql);
	$production_data_arr=array();
	$production_data_arr2=array();
	$production_po_data_arr=array();
	$production_serial_arr=array();
	$reso_line_ids=''; 
	$all_po_id_arr=array();
	$all_style_arr=array();
	$active_days_arr=array();
	$duplicate_date_arr=array();
	$poIdArr=array();
	$prod_line_array = array();
	$line_style_chk_array = array();
	$line_hour_wise_job_array = array();
	$line_wise_job_info_array = array();
	$lc_com_array = array();
	$line_last_prod_hour_array = array();
	$po_item_wise_hourly_prod_qty_arr=array();
	foreach($sql_resqlt as $val)
	{	
		$prod_line_array[$val[csf('sewing_line')]] = $val[csf('sewing_line')];
		$lc_com_array[$val[csf('company_id')]] = $val[csf('company_id')];
		$poIdArr[$val[csf('po_break_down_id')]] = $val[csf('po_break_down_id')];	 
		$line_last_prod_hour_array[$val[csf('sewing_line')]]++;	 
		if($val[csf('prod_reso_allo')]==1)
		{
			// $sewing_line_id=$prod_reso_arr[$val[csf('sewing_line')]];
			$reso_line_ids.=$val[csf('sewing_line')].',';	
			$sewing_line_ids=$prod_reso_arr[$val[csf('sewing_line')]];
			$sl_ids_arr = explode(",", $sewing_line_ids);
			$sewing_line_id = $sl_ids_arr[0]; // always 1st line id will take
		}
		else
		{
			$sewing_line_id=$val[csf('sewing_line')];
		}
		
		if($lineSerialArr[$sewing_line_id]=="")
		{
			$lastSlNo++;
			$slNo=$lastSlNo;
			$lineSerialArr[$sewing_line_id]=$slNo;
		}
		else $slNo=$lineSerialArr[$sewing_line_id];
		$production_serial_arr[$val[csf('floor_id')]][$slNo][$val[csf('sewing_line')]]=$val[csf('sewing_line')];
		
		$line_start=$line_number_arr[$val[csf('sewing_line')]][$val[csf('production_date')]]['prod_start_time'];
		if($line_start!="") 
		{ 
			$line_start_hour=substr($line_start,0,2); 
			if(substr($line_start_hour,0,1)==0)  $line_start_hour=substr($line_start_hour,1,1);	
		}
		else
		{
			$line_start_hour=$hour; 
		}
		$p = 1;
	 	for($h=$hour;$h<$last_hour;$h++)
		{
			$prod_hour="prod_hour".substr($start_hour_arr[$h],0,2)."";
			if($p<=11)
			{
				// echo $val[csf('sewing_line')]."==".$prod_hour."==".$val[csf($prod_hour)]."<br>";
				$production_data_arr[$val[csf('sewing_line')]][$prod_hour]+=$val[csf($prod_hour)]; 
				$production_po_data_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]][$val[csf('item_number_id')]]+=$val[csf($prod_hour)];
				if($line_hour_wise_job_array[$val[csf('sewing_line')]][substr($val[csf('prod_hour')],0,2)-0]=="")
				{
					$line_hour_wise_job_array[$val[csf('sewing_line')]][substr($val[csf('prod_hour')],0,2)-0] = $val[csf('job_no_prefix_num')];
				}
				else
				{
					$line_hour_wise_job_array[$val[csf('sewing_line')]][substr($val[csf('prod_hour')],0,2)-0] .= ",".$val[csf('job_no_prefix_num')];
				}
			}
			else
			{
				// $prod_hour="prod_hour".substr($start_hour_arr[$h],0,2)."";
				// echo $val[csf('sewing_line')]."==".$prod_hour."==".$val[csf($prod_hour)]."<br>";
				$production_data_arr[$val[csf('sewing_line')]]['last_hour']+=$val[csf($prod_hour)]; 
				$production_po_data_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]][$val[csf('item_number_id')]]+=$val[csf($prod_hour)];
				if($line_hour_wise_job_array[$val[csf('sewing_line')]][substr($val[csf('prod_hour')],0,2)-0]=="")
				{
					$line_hour_wise_job_array[$val[csf('sewing_line')]][substr($val[csf('prod_hour')],0,2)-0] = $val[csf('job_no_prefix_num')];
				}
				else
				{
					$line_hour_wise_job_array[$val[csf('sewing_line')]][substr($val[csf('prod_hour')],0,2)-0] .= ",".$val[csf('job_no_prefix_num')];
				}
			}
			$production_data_arr2[$val[csf('sewing_line')]][$prod_hour]+=$val[csf($prod_hour)]; 
			$po_item_wise_hourly_prod_qty_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]][$val[csf('item_number_id')]]['qty'][$prod_hour]+=$val[csf($prod_hour)];
			$p++;
			
			/* if(str_replace("'","",$actual_production_date)>=str_replace("'","",$actual_date)) 
			{
				if( $h>=$line_start_hour && $h<=$actual_time)
				{
					$production_po_data_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]]+=$val[csf($prod_hour)]; 
				} 	
			}
			
			if(str_replace("'","",$actual_production_date)<str_replace("'","",$actual_date)) 
			{	
				$production_po_data_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]]+=$val[csf($prod_hour)];
			} */
		}
		
		/* if(str_replace("'","",$actual_production_date)>=str_replace("'","",$actual_date)) 
		{	
			if( $h>=$line_start_hour && $h<=$actual_time)
			{
				$production_po_data_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]]+=$val[csf('prod_hour23')];     
			} 	
		}
		else
		{
			$production_po_data_arr[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]]+=$val[csf('prod_hour23')];     
		} */
		
	 	// $production_data_arr[$val[csf('sewing_line')]]['prod_hour23']+=$val[csf('prod_hour23')];  
		$production_data_arr[$val[csf('sewing_line')]]['prod_reso_allo']=$val[csf('prod_reso_allo')]; 
				
		if($line_wise_job_info_array[$val[csf('sewing_line')]]['job_no']!="")
		{
			$line_wise_job_info_array[$val[csf('sewing_line')]]['job_no'].="**".$val[csf('job_no')];
			$line_wise_job_info_array[$val[csf('sewing_line')]]['buyer_name'].="**".$buyerArr[$val[csf('buyer_name')]];
			$line_wise_job_info_array[$val[csf('sewing_line')]]['style'].="**".$val[csf('style_ref_no')];
			$line_wise_job_info_array[$val[csf('sewing_line')]]['po_number'].="**".$val[csf('po_number')];
			$line_wise_job_info_array[$val[csf('sewing_line')]]['country_id'].="**".$countryArr[$val[csf('country_id')]];
			$line_wise_job_info_array[$val[csf('sewing_line')]]['color_number_id'].="**".$colorArr[$val[csf('color_number_id')]];
			$line_wise_job_info_array[$val[csf('sewing_line')]]['shipment_date'].="**".$val[csf('shipment_date')];
		}
	 	else
		{
			$line_wise_job_info_array[$val[csf('sewing_line')]]['job_no']=$val[csf('job_no')]; 
			$line_wise_job_info_array[$val[csf('sewing_line')]]['buyer_name']=$buyerArr[$val[csf('buyer_name')]]; 
			$line_wise_job_info_array[$val[csf('sewing_line')]]['style']=$val[csf('style_ref_no')]; 
			$line_wise_job_info_array[$val[csf('sewing_line')]]['po_number']=$val[csf('po_number')]; 
			$line_wise_job_info_array[$val[csf('sewing_line')]]['country_id']=$countryArr[$val[csf('country_id')]]; 
			$line_wise_job_info_array[$val[csf('sewing_line')]]['color_number_id']=$colorArr[$val[csf('color_number_id')]]; 
			$line_wise_job_info_array[$val[csf('sewing_line')]]['shipment_date']=$val[csf('shipment_date')]; 
		}
	
	 	if($production_data_arr[$val[csf('sewing_line')]]['job_no']!="")
		{
			$production_data_arr[$val[csf('sewing_line')]]['job_no'].=",".$val[csf('job_no_prefix_num')];
			$production_data_arr[$val[csf('sewing_line')]]['po_id'].=",".$val[csf('po_break_down_id')]; 
		}
	 	else
		{
			$production_data_arr[$val[csf('sewing_line')]]['job_no']=$val[csf('job_no_prefix_num')];
			$production_data_arr[$val[csf('sewing_line')]]['po_id']=$val[csf('po_break_down_id')]; 
		}
		$production_data_arr[$val[csf('sewing_line')]]['quantity']+=$val[csf('good_qnty')];
		$production_data_arr_qty[$val[csf('sewing_line')]][$val[csf('po_break_down_id')]][$val[csf('item_number_id')]]['quantity']+=$val[csf('good_qnty')];

		if($production_data_arr[$val[csf('sewing_line')]]['item_number_id']!="")
		{
			$production_data_arr[$val[csf('sewing_line')]]['item_number_id'].="****".$val[csf('po_break_down_id')]."**".$val[csf('item_number_id')]."**".$val[csf('job_no')];
		}
		else
		{
			 $production_data_arr[$val[csf('sewing_line')]]['item_number_id']=$val[csf('po_break_down_id')]."**".$val[csf('item_number_id')]."**".$val[csf('job_no')]; 
		}

		$style_wise_po_arr[$val[csf('style_ref_no')]][$val[csf('po_break_down_id')]] = $val[csf('po_break_down_id')];
		
		$all_po_id_arr[$val[csf('po_break_down_id')]] = $val[csf('po_break_down_id')];
		$all_style_arr[$val[csf('style_ref_no')]] = $val[csf('style_ref_no')];
	}


	// echo "<pre>";print_r($line_last_prod_hour_array);die;
	// echo "<pre>"; print_r($production_data_arr);die;

	
	$poIds_cond = where_con_using_array($all_po_id_arr,0,"b.id");
	$poIds_cond2 = where_con_using_array($all_po_id_arr,0,"c.id");
	$poIds_cond3 = where_con_using_array($all_po_id_arr,0,"po_break_down_id");
	$style_cond = where_con_using_array($all_style_arr,1,"a.style_ref");

	
	
	/*===================================================================================== /
	/										smv sorce 										/
	/===================================================================================== */
	if(str_replace("'","",$cbo_source)==2)
	{
		$sql_item="SELECT b.id, b.SMV, c.GMTS_ITEM_ID from SUBCON_ORD_DTLS b where  b.status_active=1 and b.is_deleted=0 $poIds_cond";
		// echo $sql_item;
		$resultItem=sql_select($sql_item);
		
		foreach($resultItem as $itemData)
		{
			$item_smv_array[$itemData[csf('id')]][$itemData[csf('gmts_item_id')]]=$itemData[csf('smv_pcs')];			
		}	
	}
	else
	{
		$lc_com_ids = implode(",",$lc_com_array);
		$smv_source=return_field_value("smv_source","variable_settings_production","company_name in ($lc_com_ids) and variable_list=25 and   status_active=1 and is_deleted=0");
		//echo $manufacturing_company;
		
		if($smv_source=="") $smv_source=0; else $smv_source=$smv_source;
		if($smv_source==3) // from gsd enrty
		{
			$style_nos="'".implode("','",$all_style_arr)."'";
			$sql_item="SELECT a.id,a.APPLICABLE_PERIOD,a.BULLETIN_TYPE,A.TOTAL_SMV,A.STYLE_REF,a.GMTS_ITEM_ID  from PPL_GSD_ENTRY_MST a where a.APPLICABLE_PERIOD <= $txt_date and A.IS_DELETED=0 and A.STATUS_ACTIVE=1 $style_cond  group by a.id,a.APPLICABLE_PERIOD,a.BULLETIN_TYPE,A.TOTAL_SMV,A.STYLE_REF,a.GMTS_ITEM_ID ORDER BY a.GMTS_ITEM_ID ,a.APPLICABLE_PERIOD  DESC , a.id DESC";//and a.APPROVED=1
			$gsdSqlResult=sql_select($sql_item);
			//echo $sql_item;die;
			
			foreach($gsdSqlResult as $rows)
			{
				foreach($style_wise_po_arr[$rows['STYLE_REF']] as $po_id)
				{
					if($item_smv_array[$po_id][$rows['GMTS_ITEM_ID']]=='')
					{
						$item_smv_array[$po_id][$rows['GMTS_ITEM_ID']]=$rows['TOTAL_SMV'];
					}
				}
			}
		}
		else
		{
			$sql_item="SELECT b.id, a.set_break_down, c.gmts_item_id, c.set_item_ratio, c.smv_pcs, c.smv_pcs_precost,c.smv_set from wo_po_details_master a,wo_po_break_down b, wo_po_details_mas_set_details c where b.job_id=a.id and b.job_id=c.job_id and a.company_name in($lc_com_ids) $poIds_cond and a.is_deleted=0 and a.status_active=1 and b.is_deleted=0 and b.status_active=1";
			// echo $sql_item;
			$resultItem=sql_select($sql_item);
			
			foreach($resultItem as $itemData)
			{
				if($smv_source==1)
				{
					$item_smv_array[$itemData[csf('id')]][$itemData[csf('gmts_item_id')]]=$itemData[csf('smv_pcs')];
				}
				if($smv_source==2)
				{
					$item_smv_array[$itemData[csf('id')]][$itemData[csf('gmts_item_id')]]=$itemData[csf('smv_pcs_precost')];
				}
			}
		}
	}
	
	
	
	// echo $sql_item;
	// echo "$smv_source=<pre>";print_r($item_smv_array);echo "</pre>";
	/*===================================================================================== /
	/										po active days									/
	/===================================================================================== */
	if(str_replace("'","",$cbo_source)==2)
	{
		$po_active_sql="SELECT a.line_id as sewing_line,a.production_date,b.JOB_NO_MST as job_no from  SUBCON_ORD_DTLS b,pro_garments_production_mst a where b.id=a.order_id and a.production_type=2 and  a.status_active=1 and a.is_deleted=0 and b.is_deleted=0  $company_name $location $floor $subcon_line $buyer_id_cond  $poIds_cond group by  a.line_id,a.production_date,b.JOB_NO_MST";
		//echo $po_active_sql;die;
		foreach(sql_select($po_active_sql) as $vals)
		{
			$prod_dates=$vals[csf('production_date')];
			if($duplicate_date_arr[$vals[csf('sewing_line')]][$vals[csf('job_no')]][$prod_dates]=="")
			{
				$active_days_arr[$vals[csf('sewing_line')]][$vals[csf('job_no')]]++;
				$duplicate_date_arr[$vals[csf('sewing_line')]][$vals[csf('job_no')]][$prod_dates]=$prod_dates;
			}
		}
	}
	else
	{
		$po_active_sql="SELECT a.sewing_line,a.production_date,b.job_no_prefix_num as job_no from  wo_po_details_master b, wo_po_break_down c,pro_garments_production_mst a where a.production_type=5 and a.po_break_down_id=c.id and c.job_no_mst=b.job_no and  a.status_active=1 and a.is_deleted=0 and b.is_deleted=0 and c.is_deleted=0 $company_name $location $floor $line $buyer_id_cond  $poIds_cond2 group by  a.sewing_line,a.production_date,b.job_no_prefix_num";
		//echo $po_active_sql;die;
		foreach(sql_select($po_active_sql) as $vals)
		{
			$prod_dates=$vals[csf('production_date')];
			if($duplicate_date_arr[$vals[csf('sewing_line')]][$vals[csf('job_no')]][$prod_dates]=="")
			{
				$active_days_arr[$vals[csf('sewing_line')]][$vals[csf('job_no')]]++;
				$active_days_arr_powise[$vals[csf('po_break_down_id')]][$vals[csf('item_number_id')]]+=1;
				$duplicate_date_arr[$vals[csf('sewing_line')]][$vals[csf('job_no')]][$prod_dates]=$prod_dates;
			}
		}
	}
	// echo "<pre>"; print_r($active_days_arr);
	

	/*===================================================================================== /
	/							prod resource data no prod line								/
	/===================================================================================== */	
	// echo $prod_reso_allo[0]; die;

	if(count($prod_line_array)>0)
	{
		$prod_line_id_cond = where_con_using_array($prod_line_array,0," a.id not ");
	}
	
	if($prod_reso_allo[0]==1)
	{
		$dataArray_sql=sql_select("SELECT a.id, a.floor_id from prod_resource_mst a, prod_resource_dtls b,prod_resource_dtls_mast c where a.id=c.mst_id and c.id=b.mast_dtl_id and a.company_id=$company_id and b.pr_date=$txt_date and a.is_deleted=0 and b.is_deleted=0 and c.is_deleted=0 $prod_res_cond $prod_line_id_cond  ");//and a.id not in($prod_line_ids)
		
		foreach($dataArray_sql as $val)
		{
			// $sewing_line_id=$prod_reso_arr[$val[csf('id')]];		
			$sewing_line_ids=$prod_reso_arr[$val[csf('id')]];
			$sl_ids_arr = explode(",", $sewing_line_ids);
			$sewing_line_id = $sl_ids_arr[0]; // always 1st line id will take	
			
			if($lineSerialArr[$sewing_line_id]=="")
			{
				$lastSlNo++;
				$slNo=$lastSlNo;
				$lineSerialArr[$sewing_line_id]=$slNo;
			}
			else $slNo=$lineSerialArr[$sewing_line_id];
			$production_serial_arr[$val[csf('floor_id')]][$slNo][$val[csf('id')]]=$val[csf('id')];		
		}		
	}
		
	// echo "<pre>"; print_r($production_data_arr);die;

	
	// $tbl_width = 715+($last_hour - ($hour+1))*40;
	$p = 1;
	$tot_td = 0;
	for($k=$hour; $k<=$last_hour; $k++)
	{
		$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
										
		if($p <= 12)
		{	
			$tot_td++;
		}
		$p++;
	}
	$tbl_width = $page_width-10;
	$td_width = round(($tbl_width-825)/$tot_td);
	$page_height = $page_height - 60;

	$floor_wise_tot_line = array();
	$gr_tot_line = 0;
	$gr_tot_floor = 0;
	$line_wise_prod_arr = array();
	foreach ($production_serial_arr as $fkey => $fvalue) 
	{
		foreach ($fvalue as $slkey => $slvalue) 
		{
			foreach ($slvalue as $lkey => $val) 
			{
				$floor_wise_tot_line[$fkey]++;
				$gr_tot_line++;

				// ================ check line wise production ==============
				$p=1;
				for($k=$hour; $k<=$last_hour; $k++)
				{
					if($p<=12)
					{
						$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
						$line_wise_prod_arr[$fkey][$slkey][$lkey] += $production_data_arr[$lkey][$prod_hour];
					}
					else
					{
						$line_wise_prod_arr[$fkey][$slkey][$lkey] += $production_data_arr[$lkey]['last_hour'];
					}
					
				}
			}
		}
		$gr_tot_floor++;
	}

	$tot_tr = $gr_tot_floor+$gr_tot_line+2;
	$tr_height = ceil(($page_height-16)/$tot_tr);
	$font_size = ceil(($tr_height/5))."px";

	// echo "<pre>"; print_r($line_wise_prod_arr);die;
	$floor_name = "";
	$fid_arr = array_filter(explode(",", $cbo_floor_id));
	if(count($fid_arr)>0)
	{
		foreach ($fid_arr as $val) 
		{
			$floor_name.= ($floor_name=="") ? $floorArr[$val] : ",".$floorArr[$val];
		}
	}
	else
	{
		$floor_name = "All Unit";
	}

	$line_name = "";
	$line_id_arr = array_filter(explode(",", str_replace("'","",$hidden_line_id)));
	if(count($line_id_arr)>0)
	{
		foreach ($line_id_arr as $v) 
		{
			$line_number=explode(",",$prod_reso_arr[$v]);
			foreach($line_number as $val)
			{
				if($line_name=='') $line_name=$lineArr[$val]; else $line_name.=",".$lineArr[$val];
			}
		}
	}
	else
	{
		$line_name = "All Line";
	}
	// echo $prod_start_hour;
	// $time1 = strtotime($prod_start_hour.":00");
	// echo $txt_date;
	/* $time1 = strtotime($hour.":00");
	$time2 = strtotime(date('H:i:s'));
	// echo $time2."==".strtotime(str_replace("'","",$txt_date));die;
	
	if(substr($global_start_lanch,0,2) < substr(date('H:i:s'),0,2))
	{
		// $difference_hour = round(((abs($time2 - $time1) / 3600)),0);
		$difference_hour = (int)(abs($time2 - $time1) / 3600);
		$difference_hour = $difference_hour - 1;
		// echo $difference_hour."==SSSSSSSS";
	}
	else
	{
		$difference_hour = round(((abs($time2 - $time1) / 3600)),0);
	} */

	$time1 = $hour;
	$time2 = date('H');
	// echo $time2."==".strtotime(str_replace("'","",$txt_date));die;
	
	if(substr($global_start_lanch,0,2) < $time2)
	{
		// $difference_hour = round(((abs($time2 - $time1) / 3600)),0);
		$cur_difference_hour = (int) $time2 - $time1;
		$cur_difference_hour = $cur_difference_hour - 1;
		// echo $cur_difference_hour."==SSSSSSSS";
	}
	else
	{
		$cur_difference_hour = (int) $time2 - $time1;
	}
	
	// echo substr($global_start_lanch,0,2)."==".$difference_hour."==".$hour."==".substr(date('H:i:s'),0,2);
	ob_start();
    ?>          
	<div style="width:<? echo $tbl_width;?>px;background:#000000;">
		<style type="text/css">
			td div{font-weight: bold;font-size: 14px;vertical-align: middle;}
			#new_style div { position: relative; }
			td#new_style div::before { position: absolute; left: 0; top: 0; width: 100%; height: 50%; background: #ffe800;z-index: 99999; }
			#new_style div { box-shadow: inset 0px 7px 0px #ffe800; }
			.rpt_table tfoot th, td,td p{font-weight: bold;vertical-align: middle;color: #FFFFFF;text-shadow: rgb(0, 0, 0) 1px 0px 0px, rgb(0, 0, 0) 0.540302px 0.841471px 0px, rgb(0, 0, 0) -0.416147px 0.909297px 0px, rgb(0, 0, 0) -0.989993px 0.14112px 0px, rgb(0, 0, 0) -0.653644px -0.756803px 0px, rgb(0, 0, 0) 0.283662px -0.958924px 0px, rgb(0, 0, 0) 0.96017px -0.279416px 0px;
				}
				/* text-shadow: rgb(0, 0, 0) 1px 0px 0px, rgb(0, 0, 0) 0.540302px 0.841471px 0px, rgb(0, 0, 0) -0.416147px 0.909297px 0px, rgb(0, 0, 0) -0.989993px 0.14112px 0px, rgb(0, 0, 0) -0.653644px -0.756803px 0px, rgb(0, 0, 0) 0.283662px -0.958924px 0px, rgb(0, 0, 0) 0.96017px -0.279416px 0px; */
			#table_body thead th,#table_body tfoot th{background: #191A19;}
			#table_body thead th{color: #FFFFFF;font-weight: bold;font-size: 16px;}
			#table_body tfoot th{font-weight: bold;font-size: 16px;}
			#table_body  tr{border-bottom: .001em solid #444;}
			#table_body th,#table_body td{border-right: .001em solid #444 ; padding: 0 .5px 0 .5px;}
			.rpt_info tr td{color: #000000;font-weight: bold;font-size: 16px;}
			#table_body tbody tr th{font-size: 20px; color:red;}
			
			td div:hover span {
				bottom: 50px;
				visibility: visible;
				opacity: 1;
				z-index: 999999;
				display: block;
				text-shadow: none;
			}

			td.parentCell, div.block_div
			{
				position: relative;
			}
			th div.block_div
			{
				position: relative;
				height: 100%;
				width: 100%;
			}

			span.tooltips{
				display: none;
				position: absolute;
				z-index: 100;
				background: white;
				padding: 3px;
				color: #000000;
				top: 20px;
				left: 20px;
				font-size: 14px;
				font-weight: bold;
				text-shadow: none;
				width: 150px;
			}
			div.block_div span.tooltips{width: 80px;}
			td.parentCell:hover span.tooltips,div.block_div:hover span.tooltips{display:block;}

		</style>
       
            <table width="<? echo $tbl_width;?>" cellpadding="1" cellspacing="0" border="0" rules="all" id="table_body" align="left" style="border-collapse:seperate;border:.001em solid #444;">
				<thead>
					<tr height="<?=$tr_height;?>" style="font-size: <?=$font_size;?>">
						<th width="60"><p>Line</p></th>
						<th width="75"><p>Job</p></th>
						<th width="150"><p>Gmts Item</p></th>
						<th width="45"><p>OP</p></th>
						<th width="45"><p>HP</p></th>
						<th width="45"><p>MP</p></th>
						<th width="45"><p>SMV</p></th>
						<th width="60"><p>Day <br>Target</p></th>
						<th width="60"><p>Target/<br>Hr</p></th>
					<?
					// print_r($production_hour);
						$p = 1;
						for($k=$hour; $k<=$last_hour; $k++)
						{
							$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
															
							if($p <= 12)
							{
								if($p==12)
								{
									$time = '';//substr($start_hour_arr[$k],0,5)."-23.00";
								}
								else
								{
									$time = substr($start_hour_arr[$k],0,5)."-".substr($start_hour_arr[$k+1],0,5);
								}
								?>
								<th width="<?=$td_width;?>" ><div class="block_div"><?  echo "H".$p; ?><span class="tooltips"><?=$time;?></span></div></th>
								<?	
							}
							/*elseif ($production_hour[$prod_hour]>0 && $p>11) 
							{
								?>
								<th width="40" style="vertical-align:middle"><div class="block_div"><?  echo "H".$p; ?></div></th>
								<?
							}*/
							$p++;
						}
						?> 
						<th width="60"><p>TTL <br>Prod.</p></th>
						<th width="60"><p>Target Hit Rate%</p></th>                   
						<th width="60" title="TTL Production*SMV/[Total Manpower*6*0Working Hour]"><p>Line <br>Effi. %</p></th>                   
						<th width="60"><p>Days <br>Run</p></th> 
					</tr>
				</thead>              
                <tbody>
                	<?
                	$i=1;
					$line_wise_smv_array = array();
                	$gr_op  = 0;
                	$gr_hp  = 0;
                	$gr_mp  = 0;
                	$gr_day_trgt  = 0;
                	$gr_trgt_hr  = 0;
                	$gr_ttl_prd  = 0;
                	$gr_tot_smv  = 0;
                	$gr_tot_achv  = 0;
                	$gr_tot_effi  = 0;
                	$total_arr = array();
                	// ksort($production_serial_arr);
                	foreach($production_serial_arr as $floor_id=>$flr_data) 
                	{
                		$f_total_arr = array();
	                	$flr_op  = 0;
	                	$flr_hp  = 0;
	                	$flr_mp  = 0;
	                	$flr_day_trgt  = 0;
	                	$flr_trgt_hr  = 0;
	                	$flr_ttl_prd  = 0;
	                	$flr_tot_achv  = 0;
	                	$flr_tot_effi  = 0;
	                	$flr_tot_smv = 0;
                		ksort($flr_data);
	                	foreach($flr_data as $sl=>$sl_data) 
	                	{
	                		foreach($sl_data as $l_id=>$ldata)
							{   
								$bgcolor=($i%2==0)?"#000000":"#000000";
								$sewing_line='';
								if($production_data_arr[$l_id]['prod_reso_allo']!="")
								{
									if($production_data_arr[$l_id]['prod_reso_allo']==1)
									{
										$line_number=explode(",",$prod_reso_arr[$l_id]);
										foreach($line_number as $val)
										{
											if($sewing_line=='') $sewing_line=$lineArr[$val]; else $sewing_line.=",".$lineArr[$val];
										}
									}
									else $sewing_line=$lineArr[$l_id];
								}
								else
								{
									$line_number=explode(",",$prod_reso_arr[$l_id]);
									foreach($line_number as $val)
									{
										if($sewing_line=='') $sewing_line=$lineArr[$val]; else $sewing_line.=",".$lineArr[$val];
									}
									
								}         
								$terget_hour = $prod_resource_array[$l_id]['terget_hour'];    

								$germents_item=array_unique(explode('****',$production_data_arr[$l_id]['item_number_id']));
								// print_r($germents_item);die;

								$item_smv="";
								$item_name="";
								$tot_smv = 0;
								$item_smv_count=0;
								$produce_minit=0;
								$chk_smv_arr = array();
								$style_running_arr = array();
								foreach($germents_item as $g_val)
								{									
									$po_garment_item=explode('**',$g_val);

										// if($item_smv!='') $item_smv.='/';
										// echo $po_garment_item[0].'='.$po_garment_item[1]."**".$item_smv_array[$po_garment_item[0]][$po_garment_item[1]]."<br>";	
										if($prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]]!="")
										{
											if($chk_smv_arr[$l_id][$po_garment_item[0]][$po_garment_item[1]])	
											{				
												if($item_smv!='') $item_smv.='/';	
												if(strpos($prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]],"."))
												{	
													$item_smv.=number_format($prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]],2);
												}
												else
												{
													$item_smv.=number_format($prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]],2);
												}
												$chk_smv_arr[$prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]]] = $prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]];
												// $flr_tot_smv += $prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]];
												// $gr_tot_smv += $prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]];

												$produce_minit+=$production_po_data_arr[$l_id][$po_garment_item[0]][$po_garment_item[1]]*$prod_resource_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]];
											}
										}
										else
										{
											if($chk_smv_arr[$po_garment_item[0]][$po_garment_item[1]]=="")	
											{				
												if($item_smv!='') $item_smv.='/';
												if(strpos($item_smv_array[$l_id][$po_garment_item[0]][$po_garment_item[1]],"."))
												{		
													$item_smv.=number_format($item_smv_array[$po_garment_item[0]][$po_garment_item[1]],2);
												}
												else
												{
													$item_smv.=number_format($item_smv_array[$po_garment_item[0]][$po_garment_item[1]],2);
												}
												$chk_smv_arr[$item_smv_array[$po_garment_item[0]][$po_garment_item[1]]] = $item_smv_array[$po_garment_item[0]][$po_garment_item[1]];
												// $flr_tot_smv += $item_smv_array[$po_garment_item[0]][$po_garment_item[1]];
												// $gr_tot_smv += $item_smv_array[$po_garment_item[0]][$po_garment_item[1]];

												$produce_minit+=$production_po_data_arr[$l_id][$po_garment_item[0]][$po_garment_item[1]]*$item_smv_array[$po_garment_item[0]][$po_garment_item[1]];
												// echo $sewing_line."==".$l_id."==".$production_po_data_arr[$l_id][$po_garment_item[0]][$po_garment_item[1]]."*".$item_smv_array[$po_garment_item[0]][$po_garment_item[1]]."<br>";
												
											}

										}
										$item_name .= ($item_name=="") ? $garments_item[$po_garment_item[1]] : ",".$garments_item[$po_garment_item[1]];
										$line_wise_smv_array[$l_id][$po_garment_item[2]] = $item_smv_array[$po_garment_item[0]][$po_garment_item[1]];		
										$tot_smv += $item_smv_array[$po_garment_item[0]][$po_garment_item[1]];	
										$item_smv_count++;						
													

										// calculate job/style wise line running hour ===================
										
										for($k=$hour; $k<=$last_hour; $k++)
										{
											$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
											
											if($po_item_wise_hourly_prod_qty_arr[$l_id][$po_garment_item[0]][$po_garment_item[1]]['qty'][$prod_hour]>0)
											{
												$style_running_arr[$job][$prod_hour]++;
											}											
											
											// $style_prod_qty_arr[$l_id][$job] += $po_item_wise_hourly_prod_qty_arr[$l_id][$po_garment_item[0]][$po_garment_item[1]]['qty'][$prod_hour];
										}
										// $style_running_arr[$job] += $line_tot_hour;
										$style_smv_arr[$job] = $item_smv_array[$po_garment_item[0]][$po_garment_item[1]];
								}	
								
								// echo "<pre>";print_r($style_prod_qty_arr);
								// $avg_unit_price = $unit_price/$po_count;
								$job_tot_smv = 0;
								$job_tot_min = 0;
								foreach ($style_smv_arr as $j_key => $job_smv) 
								{
									$running_hour = count($style_running_arr[$j_key]);
									$job_tot_min += $running_hour * $style_smv_arr[$j_key];
									$job_tot_smv += $style_smv_arr[$j_key];
									// echo $running_hour ."*". $style_smv_arr[$j_key]."<br>";
								}
								$style_count = array_unique(array_filter(explode("**",$line_wise_job_info_array[$l_id]['job_no'])));
								$avg_smv = 0;
								if(count($style_count)>1) // when multiple style prod in a line
								{
									$avg_smv = number_format(($job_tot_min/$job_tot_smv),2);
									// echo $line_tot_hour."/".$line_tot_smv."<br>";
								}
								else
								{
									$avg_smv = ($item_smv_count>0) ? number_format(($tot_smv/$item_smv_count),2) : '0.00';
								}
								$flr_tot_smv += $avg_smv;
								$gr_tot_smv += $avg_smv;							

								// print_r($line_wise_smv_array);die;
								 $item_smv = "";
								foreach ($line_wise_smv_array[$l_id] as $jkey => $smv) 
								{
									$item_smv .= ($item_smv=="") ? number_format($smv,2) : "/".number_format($smv,2);
									// $flr_tot_smv += number_format($smv,2);
									// $gr_tot_smv += number_format($smv,2);
								} 


								$general_prod_qty = 0;
								$ot_prod_qty = 0;
								$gen_last_prod_hour = "";
								$ot_last_prod_hour = "";
								$m=1;
								for($k=$hour; $k<=$last_hour; $k++)
								{
									$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2).""; 	
									if($m<=9)
									{
										if($production_data_arr2[$l_id][$prod_hour]>0)
										{
											$gen_last_prod_hour=substr($start_hour_arr[$k],0,2);
										}
									}
									else
									{
										// echo $production_data_arr[$l_id][$prod_hour];
										if($production_data_arr2[$l_id][$prod_hour]>0)
										{
											$ot_last_prod_hour=substr($start_hour_arr[$k],0,2);
										}
									}
									$m++;													
								}
								// echo $ot_last_prod_hour;die('ddd');
								$line_prod_hour_array = array();
								$current_hour = 0;
								if(strtotime(date('d-M-Y')) != strtotime(str_replace("'","",$txt_date)))
								{
									for($k=$hour; $k<=$last_hour; $k++)
									{
										$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
										// echo $r['qty'][$prod_hour]."<br>";
										if($production_data_arr2[$l_id][$prod_hour]>0)
										{
											$line_prod_hour_array[$l_id]++;
										}

									}
									$difference_hour = $line_prod_hour_array[$l_id];
									
									if($ot_last_prod_hour!="")
									{
										$current_hour = $ot_last_prod_hour - $hour;
									}
									else
									{
										if(substr($global_start_lanch,0,2) < substr(date('H:i:s'),0,2))
										{
											$current_hour = $prod_resource_array[$l_id]['working_hour']-1;
										}
										else
										{
											$current_hour = $prod_resource_array[$l_id]['working_hour'];
										}
									}
								}
								else
								{
									if($ot_last_prod_hour!="")
									{
										$current_hour = $ot_last_prod_hour - $hour;
									}
									else
									{
										$current_hour = $cur_difference_hour;
									}
								}
								
								$efficiency_min=($prod_resource_array[$l_id]['man_power'])*$current_hour*60;								 
								// echo $sewing_line."==(".$prod_resource_array[$l_id]['man_power'].")*".$difference_hour."*60.<br>";								 
								$line_efficiency=(($produce_minit)*100)/$efficiency_min;
								// echo $sewing_line."==((".$produce_minit.")*100)/".$efficiency_min."<br>";

								$job_no_arr = array_unique(explode(",", $production_data_arr[$l_id]['job_no']));
								$active_days = "";
								foreach ($job_no_arr as $j_key => $job) 
								{
									$active_days .= ($active_days=="") ? $active_days_arr[$l_id][$job] : "/".$active_days_arr[$l_id][$job];
								}
								$job_no = implode(",", $job_no_arr);
								$style = "";
								if($line_wise_prod_arr[$floor_id][$sl][$l_id]<1)
								{
									$style = "color: #949494;";
								}

								// ============== for title ===============
								$title = "";
								$buyer = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['buyer_name']))));
								$jobNo = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['job_no']))));
								$styleName = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['style']))));
								$colorName = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['color_number_id']))));
								$POName = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['po_number']))));
								$countryName = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['country_id']))));
								$shipment_date = implode(", ", array_unique(array_filter(explode("**", $line_wise_job_info_array[$l_id]['shipment_date']))));
								$capacity = $prod_resource_array[$l_id]['capacity'];

								$title .= "Buyer : ".$buyer."&#013;";
								$title .= ", Job No : ".$jobNo."&#013;";
								$title .= ", Style : ".$styleName."&#013;";
								$title .= ", Color : ".$colorName."&#013;";
								$title .= ", PO : ".$POName."&#013;";
								$title .= ", Country : ".$countryName."&#013;";
								$title .= ", Shipment Date : ".$shipment_date."&#013;";
								$title .= ", MC Capacity : ".$capacity."&#013;";
								
			                	?>
			                   <tr height="<?=$tr_height;?>" bgcolor="<? echo $bgcolor;?>" id="tr_<?= $i;?>" onClick="change_color('tr_<?= $i; ?>','<?= $bgcolor; ?>')">
			                        <td class="parentCell" width="60"><span class="tooltips"><?=$title;?></span><p style="<?=$style;?>"><?=$sewing_line;?></p></td>
				                    <td width="75"><p style="<?=$style;?>"><?=$job_no;?></p></td>
				                    <td width="150" title="<?=implode(", ", array_unique(explode(",", $item_name)));?>"><p style="<?=$style;?>"><?=implode(", ", array_unique(explode(",", $item_name)));?></p></td>
									<?
									if($prod_resource_array2[$l_id]['man_power']!="") // when style popup data not blank
									{
										?>
										<td align="center" width="45"><p style="<?=$style;?>"><?=$prod_resource_array2[$l_id]['operator'];?></p></td>
										<td align="center" width="45"><p style="<?=$style;?>"><?=$prod_resource_array2[$l_id]['helper'];?></p></td>
										<td align="center" width="45"><p style="<?=$style;?>"><?=$prod_resource_array2[$l_id]['man_power'];?></p></td>
										<?
									}
									else
									{
										?>
										<td align="center" width="45"><p style="<?=$style;?>"><?=$prod_resource_array[$l_id]['operator'];?></p></td>
										<td align="center" width="45"><p style="<?=$style;?>"><?=$prod_resource_array[$l_id]['helper'];?></p></td>
										<td align="center" width="45"><p style="<?=$style;?>"><?=$prod_resource_array[$l_id]['man_power'];?></p></td>
										<?
									}
									// $item_smv = implode("/",array_unique(explode("/",$item_smv)));
									?>
				                    <td class="parentCell" width="45" align="center"><span class="tooltips"><?=$title;?></span><p style="<?=$style;?>"><?=$item_smv;?></p></td>
				                    <td align="right" width="60"><p style="<?=$style;?>"><?=number_format($prod_resource_array[$l_id]['tpd'],0);?></p></td>
				                    <td align="right" width="60"><p style="<?=$style;?>"><?=number_format($prod_resource_array[$l_id]['terget_hour'],0);?></p></td>
			                        <?
			                        $p = 1;
			                        $line_tot = 0;
			                        $new_color = "";
			                        $new_style = "";
			        				$job_chk_arr = array();
									for($k=$hour; $k<=$last_hour; $k++)
									{
										$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
										$color="";
										if($p <= 11)
										{
											if($k==$lunch_start_time)
											{
												$color="#333";
											}
											elseif($production_data_arr[$l_id][$prod_hour]==$terget_hour)
											{
												$color="green";
											}
											elseif ($production_data_arr[$l_id][$prod_hour]>$terget_hour) 
											{
												$color="#2037df";
											}
											elseif ($production_data_arr[$l_id][$prod_hour]<$terget_hour && $production_data_arr[$l_id][$prod_hour]>0) 
											{
												$color="#f50a10";
											}
										}
										elseif ($p==12) 
										{
											if($k==$lunch_start_time)
											{
												$color="#333";
											}
											elseif($production_data_arr[$l_id]['last_hour']==$terget_hour)
											{
												$color="green";
											}
											elseif ($production_data_arr[$l_id]['last_hour']>$terget_hour) 
											{
												$color="#2037df";
											}
											elseif ($production_data_arr[$l_id]['last_hour']<$terget_hour && $production_data_arr[$l_id]['last_hour']>0) 
											{
												$color="#f50a10";
											}
										}


										if(count($job_no_arr)>1)//when multiple job in a single line
										{
											$job_nos = $line_hour_wise_job_array[$l_id][substr($start_hour_arr[$k],0,2)+1];
											$hour_wise_job_arr = array_unique(explode(",", $job_nos));
											// echo $job_nos."=".$l_id."=".(substr($start_hour_arr[$k],0,2))."<br>";
											if($job_nos!="")
											{											
												if($p >1) // check start after 1st hour
												{
													if(!in_array($job_nos, $job_chk_arr))
													{
														$new_style = 'new_style';
														$title = 'title="'.implode(",", $hour_wise_job_arr).'"';
													}
												}
												$job_chk_arr[$job_nos]=$job_nos;
												// print_r($job_chk_arr);

												
												// echo "<pre>"; print_r($hour_wise_job_arr);	echo "</pre>";
												// echo count($hour_wise_job_arr)."<br>";
												if(count($hour_wise_job_arr)>1) // when multi job run in same line and same hour
												{
													$new_style = 'new_style';
													$title = 'title="'.implode(",", $hour_wise_job_arr).'"';
												}	
											}
											
										}										

										if($p <= 11)
										{
											?>
						                      <td bgcolor="<?=$color;?>" align="right" width="<?=$td_width;?>" id="<?=$new_style;?>" <?//=$title;?> >
						                      	<?//=$p;?>
						                      	<div style="<?=$style;?>">
						                      		<?=number_format($production_data_arr[$l_id][$prod_hour],0); ?>
						                      	</div>
						                      		
						                      	</td> 
											<?	
											$line_tot += $production_data_arr[$l_id][$prod_hour];
											// echo $production_data_arr[$l_id][$prod_hour]."<br>";
											$total_arr[$prod_hour] += $production_data_arr[$l_id][$prod_hour];
											$f_total_arr[$prod_hour] += $production_data_arr[$l_id][$prod_hour];
											// echo "<pre>";  print_r($production_data_arr[$l_id]);
										}
										elseif ($p==12) 
										{
											?>
						                      <td bgcolor="<?=$color;?>" align="right" width="<?=$td_width;?>" id="<?=$new_style;?>"><?=number_format($production_data_arr[$l_id]['last_hour'],0);?></td>
											<?	
											$line_tot += $production_data_arr[$l_id]['last_hour'];
											// echo $production_data_arr[$l_id][$prod_hour]."<br>";
											$total_arr['last_hour'] += $production_data_arr[$l_id]['last_hour'];
											$f_total_arr['last_hour'] += $production_data_arr[$l_id]['last_hour'];
										}
										$p++;
									}

									$day_target = $prod_resource_array[$l_id]['tpd'];
									$color = "";
									if($line_tot==$day_target)
									{
										$color="green";
									}
									elseif ($line_tot>$day_target) 
									{
										$color="#1e0df2";
									}
									elseif ($line_tot<$day_target && $line_tot>0) 
									{
										$color="#f50a10";
									}
			                        ?>
								    <td bgcolor="<?=$color;?>" align="right" width="60" style="<?=$style;?>"><?=number_format($line_tot,0);?></td>
									<td  bgcolor="<?=$color;?>" style="<?=$style;?>" align="right" width="60" title="(tot prod=<?=$line_tot."/(target per hour=".$prod_resource_array[$l_id]['terget_hour']."*current hour=".$current_hour."))*100";?>">
										<?=($line_tot>0) ? number_format((($line_tot/($prod_resource_array[$l_id]['terget_hour']*$current_hour))*100),2) : 0;?>%
									</td>                    
				                    <td bgcolor="<?=$color;?>" style="<?=$style;?>" align="right" width="60"><?=number_format($line_efficiency,2);?>%</td>                   
				                    <td style="<?=$style;?>" align="center" width="60">&nbsp;<?=$active_days;?></td> 
			                    </tr>
			                    <?
			                    $i++;

			                	$flr_op  += $prod_resource_array[$l_id]['operator'];
			                	$flr_hp  += $prod_resource_array[$l_id]['helper'];
			                	$flr_mp  += $prod_resource_array[$l_id]['man_power'];
			                	$flr_day_trgt  += $prod_resource_array[$l_id]['tpd'];
			                	$flr_trgt_hr  += $prod_resource_array[$l_id]['terget_hour'];
			                	$flr_ttl_prd  += $line_tot;
			                	// $flr_tot_achv  += ($line_tot/$prod_resource_array[$l_id]['tpd'])*100;
			                	$flr_tot_achv  += ($line_tot>0) ? number_format((($line_tot/($prod_resource_array[$l_id]['terget_hour']*$current_hour))*100),2) : 0;
			                	$flr_tot_effi  += $line_efficiency;

			                	$gr_op  += $prod_resource_array[$l_id]['operator'];
			                	$gr_hp  += $prod_resource_array[$l_id]['helper'];
			                	$gr_mp  += $prod_resource_array[$l_id]['man_power'];
			                	$gr_day_trgt  += $prod_resource_array[$l_id]['tpd'];
			                	$gr_trgt_hr  += $prod_resource_array[$l_id]['terget_hour'];
			                	$gr_ttl_prd  += $line_tot;
			                	$gr_tot_achv  += ($line_tot>0) ? number_format((($line_tot/($prod_resource_array[$l_id]['terget_hour']*$current_hour))*100),2) : 0;
			                	$gr_tot_effi  += $line_efficiency;

								// echo "((".$line_tot."/(".$prod_resource_array[$l_id]['terget_hour']."*".$difference_hour."))*100)<br>";
			                }
							// die;
			            }
			            ?>
			            <tr height="<?=$tr_height;?>" style="background: #082032;text-align: right;">
	                        <td colspan="3" ><?=$floorArr[$floor_id];?> : </td>
		                    <td width="45"><?=number_format($flr_op,0);?></td>
		                    <td width="45"><?=number_format($flr_hp,0);?></td>
		                    <td width="45"><?=number_format($flr_mp,0);?></td>
		                    <td width="45"><?=number_format(($flr_tot_smv/$floor_wise_tot_line[$floor_id]),2);?></td>
		                    <td width="60"><?=number_format($flr_day_trgt,0);?></td>
		                    <td width="60"><?=number_format($flr_trgt_hr,0);?></td>
	                        <?
	                        $p = 1;
	                        $total = 0;
							for($k=$hour; $k<=$last_hour; $k++)
							{
								$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
															
								if($p <= 11)
								{
									?>
				                      <td align="right" width="<?=$td_width;?>"><? echo number_format($f_total_arr[$prod_hour],0); ?></td>
									<?	
									$total += $f_total_arr[$prod_hour];
								}
								elseif ($p==12) 
								{
									?>
				                      <td align="right" width="70"><? echo $f_total_arr['last_hour']; ?></td>
									<?
									$total += $f_total_arr['last_hour'];
								}
								$p++;
								
							}
	                        ?>
						    <td width="60"><?=number_format($flr_ttl_prd,0);?></td>                   
		                    <td width="60"><?=number_format(($flr_tot_achv/$floor_wise_tot_line[$floor_id]),2);?>%</td>                  
		                    <td width="60"><?=number_format(($flr_tot_effi/$floor_wise_tot_line[$floor_id]),2);?>%</td>
		                    <td width="60"></td> 
	                    </tr>
			            <?
			        }
		            ?>
					
					<tr height="<?=$tr_height;?>" style="background: #1F1D36;text-align:right;">
                        <!-- <th width="60"></th>
	                    <th width="75"></th> -->
	                    <td colspan="3" >Grand Total : </td>
	                    <td width="45"><?=number_format($gr_op,0);?></td>
	                    <td width="45"><?=number_format($gr_hp,0);?></td>
	                    <td width="45"><?=number_format($gr_mp,0);?></td>
	                    <td width="45"><?=number_format(($gr_tot_smv/$gr_tot_line),2);?></td>
	                    <td width="60"><?=number_format($gr_day_trgt,0);?></td>
	                    <td width="60"><?=number_format($gr_trgt_hr,0);?></td>
                        <?
                        $p = 1;
                        $total = 0;
						for($k=$hour; $k<=$last_hour; $k++)
						{
							$prod_hour="prod_hour".substr($start_hour_arr[$k],0,2)."";
														
							if($p <= 11)
							{
								?>
			                      <td align="right" width="<?=$td_width;?>"><? echo number_format($total_arr[$prod_hour],0); ?></td>
								<?	
								$total += $total_arr[$prod_hour];
							}
							elseif ($p==12) 
							{
								?>
			                      <td align="right" width="70"><? echo $total_arr['last_hour']; ?></td>
								<?
								$total += $total_arr['last_hour'];
							}
							$p++;
							
						}
                        ?>
					    <td width="60"><?=number_format($gr_ttl_prd,0);?></td>                   
					    <td width="60"><?=number_format(($gr_tot_achv/$gr_tot_line),2);?>%</td>                
					    <td width="60"><?=number_format(($gr_tot_effi/$gr_tot_line),2);?>%</td> 
	                    <td width="60"></td> 
                    </tr>
                </tfoot>
            </table>
		<!-- </div> -->
		 <!-- <table class="rpt_table" width="<?= $tbl_width;?>" cellpadding="0" cellspacing="0" border="1" rules="all" align="left">
              
                <tfoot>
                </tfoot>
            </table> -->
	</div>

	<script type="text/javascript">
		$(document).ready(function () 
		{
	        /*var div_height = screen.height - 220;
	        var div_width = screen.width - 50;
	        document.getElementById("scroll_body").style.maxHeight  = div_height+'px'; 
	        document.getElementById("scroll_body").style.width  = div_width+'px'; 
	        document.getElementById("scroll_body").style.overflowY = "scroll"; */
	    });
		        
		$(function() 
		{  
			// alert('ok');
			// $(".ui-tooltips").tooltip();  
			//$(".tooltip-2").tooltip();  
		});  
	</script>
	<?    
	$user_id=($user_id=='')?1000000000000:$user_id;

	foreach (glob("$user_id*.xls") as $filename) 
	{
		@unlink($filename);
	}
	//---------end------------//
	// $name=time();
	/* $name="display_board";
	$filename=$user_id."_".$name.".xls";
	$create_new_doc = fopen($filename,'w') or die('can not open');
	$is_created = fwrite($create_new_doc,ob_get_contents()) or die('can not write'); */
	echo "$total_data####$filename####".date('d-m-Y');	
	// echo "$total_data####$filename####30-11-2022";
	disconnect($con);
	exit();      

}
?>