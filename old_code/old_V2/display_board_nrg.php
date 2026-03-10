<?php
/* -------------------------------------------- Comments -----------------------
  Purpose           :   This Form Will Create Display Report Report.
  Functionality :
  JS Functions  :
  Created by        :   Shafiq
  Creation date     :   17-07-2022
  Updated by        :
  Update date       :
  QC Performed BY   :
  QC Date           :
  Comments          :   Passion for writing beautiful and clean code
 */

session_start();
// if( $_SESSION['logic_erp']['user_id'] == "" ) header("location:login.php");
require_once('../../includes/common.php');
extract($_REQUEST);
$_SESSION['page_permission']=$permission;

//------------------------------------------------------------------------------------
echo load_html_head_contents("Display Report", "../../", 1, 1,$unicode,1,1,1);
?>  
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> -->
<script type="text/javascript">
    <? 
        $companyArr = return_library_array("select id,company_name from lib_company","id","company_name"); 
        $companyArr = json_encode($companyArr);
        echo "var companyArr= " . $companyArr . ";\n";
        
        $floorArr = return_library_array("select id,floor_name from lib_prod_floor","id","floor_name"); 
        $floorArr = json_encode($floorArr);
        echo "var floorArr= " . $floorArr . ";\n";        

        $lineArr = return_library_array("select id,line_name from lib_sewing_line where status_active=1","id","line_name"); 
        $sql = sql_select("SELECT id,line_number from prod_resource_mst where is_deleted=0");
        $line_lib = array();
        foreach ($sql as $val) 
        {
            $line_name = "";
            $line_ex = explode(",",$val['LINE_NUMBER']);
            foreach ($line_ex as $v) 
            {
                $line_name .= ( $line_name=="") ?  $lineArr[$v] : ",".$lineArr[$v];
            }

            $line_lib[$val['ID']] = $line_name;
        }
        $line_lib_arr = json_encode($line_lib);
        echo "var lineArr= " . $line_lib_arr . ";\n";
    ?>
    var comJsonData = JSON.stringify(companyArr);
    var comObj = JSON.parse(comJsonData);

    var floorJsonData = JSON.stringify(floorArr);
    var floorObj = JSON.parse(floorJsonData);

    var lineJsonData = JSON.stringify(lineArr);
    var lineObj = JSON.parse(lineJsonData);

    // console.log(lineObj);
    var idleTime = 0;
    $(document).ready(function () {
        // Increment the idle time counter every sec
        var idleInterval = setInterval(timerIncrement, 1000); // 1 sec

        // Zero the idle timer on mouse movement.
        $(this).mousemove(function (e) {
            idleTime = 0;
        });
        $(this).keypress(function (e) {
            idleTime = 0;
        });
    });

    function timerIncrement() {
        idleTime = idleTime + 1;
        if (idleTime > 10) { // 10 sec
            // $(".menu_icon").click(); right: 0px; background-position: 0px center;
            // document.getElementById("searchPanel").style.right  = '-200px'; 
            // document.getElementById("menu_icon").style.right  = '0px'; 
            // document.getElementById("menu_icon").style.backgroundPosition   = '0px center'; 
            // clearInterval(idleInterval);
            if($('.menu_icon').hasClass('open'))
            {
                $('.menu_icon').removeClass('open');
                $('.menu_icon').text('Open');
                $('.menu_icon').animate({
                    "right":"0px",
                    "background-position":"0px"
                });
                $('#searchPanel').animate({"right":"-200px"});
                document.getElementById("menu_icon").style.right  = '0px'; 
                document.getElementById("menu_icon").style.backgroundPosition   = '0px center';
                $('body').animate({
                    "right":"0px",
                    "z-index":"999"
                });
            }
        }
    }
    // alert(screen.height);
</script>
<script>
    
    /*if( $('#index_page', window.parent.document).val()!=1) window.location.href = "../../logout.php";  
    var permission = '<? echo $permission; ?>'; */
    const refresh_time = $("#refresh_time").val()*1;
    function fn_report_generated()
    {   
        /*if (form_validation('cbo_company_id*txt_sewing_date','Comapny Name*Sewing Date')==false)
        {
            return;
        }
        else
        {*/    
            var floor_ids = [];
            $(':checkbox:checked').each(function(i){
              floor_ids[i] = $(this).val();
            });
            // alert(floor_ids.join(", "));
            var floorIds = floor_ids.join(",");

            var cbo_company_name = $("#cbo_company_id").val(); 
            var cbo_buyer_name = $("#cbo_buyer_name").val(); 
            var txt_sewing_date = $("#txt_sewing_date").val(); 
            var cbo_line_id = $("#hidden_line_id").val(); 
            var cbo_source = $("#cbo_source").val(); 
            // =====================================
            $("#company_name").text("Company : "+comObj[cbo_company_name]);
            if(floorObj[floorIds]!=undefined)
            {
                $("#floor_name").text(", Unit : "+floorObj[floorIds]);
            }
            $("#prod_date").text(", Date : "+txt_sewing_date);

            if(cbo_line_id!=undefined)
            {
                var line_name = "";
                var line_ids = cbo_line_id.split(',');
                for (i = 0; i < line_ids.length; i++) 
                {
                    if(lineObj[line_ids[i]]!=undefined)
                    {
                        line_name += lineObj[line_ids[i]] + ",";
                    }
                }
                // alert(line_name);
                if(line_name!=undefined)
                {
                    if(line_name!="")
                    {
                        // alert($("#line_name").text());
                        $("#line_name").text(", Sewing Line : "+line_name);

                    }
                    else
                    {
                        $("#line_name").text(", Sewing Line : All Line");
                    }
                }
                else
                {
                    $("#line_name").text(", Sewing Line : All Line");
                }
            }
            else
            {
                $("#line_name").text(", Sewing Line : All Line");
            }


            // =====================================
            var page_width = screen.width;
            var page_height = screen.height;
            var data="action=report_generate&cbo_company_name="+cbo_company_name+"&txt_date="+txt_sewing_date+"&hidden_line_id="+cbo_line_id+"&cbo_floor_id="+floorIds+"&page_width="+page_width+"&page_height="+page_height+'&cbo_buyer_name='+cbo_buyer_name+'&cbo_source='+cbo_source;
            // freeze_window(3);
            http.open("POST","requires/display_board_nrg_controller.php",true);
            http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            http.send(data);
            http.onreadystatechange = fn_report_generated_reponse;
        // }
    }   

    fn_report_generated();     

    function fn_report_generated_reponse()
    {    
        if(http.readyState == 4) 
        {
            show_msg('3');
            var reponse=trim(http.responseText).split("####");
            var refresh_time = $("#refresh_time").val()*1000;
            $('#rptContainer').html(reponse[0]);
            //alert(reponse[1]);
            if(reponse[2]!=undefined)
            {
                // alert(reponse[2]);
                document.getElementById('txt_sewing_date').value = reponse[2];
            }
            document.getElementById('export_btn').innerHTML='<a href="##" onclick="fnExportToExcel()" target=_blank; style="text-decoration:none" id="dlink"><button type="button">Export to Excel</button></a>';
            // release_freezing();
            setTimeout(fn_report_generated,refresh_time);
            setTimeout(refreshTimer,refresh_time);
        }    
    }

    function fnExportToExcel()
	{
		// $(".fltrow").hide();
		let tableData = document.getElementById("rptContainer").innerHTML;
		// alert(tableData);
	    let data_type = 'data:application/vnd.ms-excel;base64,',
		template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table border="2px">{table}</table></body></html>',
		base64 = function (s) {
			return window.btoa(unescape(encodeURIComponent(s)))
		},
		format = function (s, c) {
			return s.replace(/{(\w+)}/g, function (m, p) { return c[p]; })
		}
		
		let ctx = {
			worksheet: 'Worksheet',
			table: tableData
		}
		
        let dt = new Date();
	    document.getElementById("dlink").href = data_type + base64(format(template, ctx));
	    document.getElementById("dlink").traget = "_blank";
        document.getElementById("dlink").download = dt.getTime()+'_display_board.xls';
	    document.getElementById("dlink").click();
		// $(".fltrow").show();
		// alert('ok');
	}

    function new_window()
    {
        var w = window.open("Surprise", "#");
        var d = w.document.open();
        d.write ('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN""http://www.w3.org/TR/html4/strict.dtd">'+'<html><head><link rel="stylesheet" href="../../css/style_common.css" type="text/css" media="print" /><title></title></head><body>'+document.getElementById('report').innerHTML+'</body</html>');
        d.close();
    }

    function showClock__() 
    {
        var date = new Date(),
        hour = date.getHours(),
        minute = checkTime(date.getMinutes()),
        ss = checkTime(date.getSeconds());
        // alert(hour);return;

        // hour = <?=date('H');?>,
        // minute = <?=date('i');?>,
        // ss = <?=date('s');?>;
        // alert(hour);return;

        function checkTime(i) {
          if( i < 10 ) {
            i = "0" + i;
          }
          return i;
        }

      if ( hour > 12 ) {
        hour = hour - 12;
        if ( hour == 12 ) {
          hour = checkTime(hour);
        document.getElementById("currentTimer").innerHTML = hour+":"+minute+":"+ss+" AM";
        }
        else {
          hour = checkTime(hour);
          document.getElementById("currentTimer").innerHTML = hour+":"+minute+":"+ss+" PM";
        }
      }
      else {
        document.getElementById("currentTimer").innerHTML = hour+":"+minute+":"+ss+" AM";;
      }
      // var time = setTimeout(digi,1000);
        var time = setTimeout(showClock,1000);

        // ======================= set current date when day end ========================= 
        if(document.getElementById("currentTimer").innerHTML=='12:00:00 AM')
        {
            var day = date.getDate()
            var month = date.getMonth() + 1
            var year = date.getFullYear();
            // alert('kakku');
            document.getElementById('txt_sewing_date').value = day + "-" + month + "-" + year;
        }
    }

    

    

    function get_sewing_line()
    {
        var floor_ids = [];
        $(':checkbox:checked').each(function(i){
          floor_ids[i] = $(this).val();
        });
        // alert(floor_ids.join(", "));
        var floorIds = floor_ids.join(",");
        load_drop_down( 'requires/display_board_nrg_controller',floorIds+'_'+document.getElementById('cbo_company_id').value+'_'+document.getElementById('txt_sewing_date').value, 'load_drop_down_sewing_line_floor', 'sewing_line' );
        get_php_form_data( floorIds, 'eval_multi_select', 'requires/display_board_nrg_controller' );
    }



    function openmypage_line() // For Line
    {
        var floor_ids = [];
        $(':checkbox:checked').each(function(i){
          floor_ids[i] = $(this).val();
        });
        // alert(floor_ids.join(", "));
        /*if (floor_ids.length === 0)
        {
            alert("Plese select floor");
            return;
        }*/
        
        var floorIds = floor_ids.join(",");

        var wo_company_name = $("#cbo_company_id").val();
        var date_from = $("#txt_sewing_date").val();
        var page_link='requires/display_board_nrg_controller.php?action=line_search_popup&wo_company_name='+wo_company_name+'&floor_name='+floorIds+'&date_from='+date_from;  
        var title="Line Popup";
        emailwindow=dhtmlmodal.open('EmailBox', 'iframe', page_link, title, 'width=250px,height=370px,center=1,resize=0,scrolling=0','../')
        emailwindow.onclose=function()
        {
            var theform=this.contentDoc.forms[0]; 
            var line_id=this.contentDoc.getElementById("txt_selected_id").value; // product ID
            var line_name=this.contentDoc.getElementById("txt_selected").value; // product ID
            $("#txt_line").val(line_name); 
            $("#hidden_line_id").val(line_id);
        }
        
    }    
    function reset_floor_and_line()
    {
        $("#line_name").text(", Unit : All Line");

        $("#floor_name").text(", Unit : All Floor");
        $("#txt_line").val('');
        $("#hidden_line_id").val('');
    }
</script>  
<style>
    body{
        background: #000000;
    }
    #searchPanel {
      position: fixed;
      top: 0;
      right: 0;
      width: 200px;
      height: 100%;
      background: #082032;
      padding:0px;
      overflow: hidden;
      z-index: 99999;
    }

    #searchPanelInner{
      border-radius: 10px;
      height: 100%;
      background: #082032;
      margin: 5px;
      padding: 5px;
      color: #ffffff;
      overflow-y: scroll;
    }


    #searchPanel ul {
      margin: 0;
      padding: 0;
    }

    #searchPanel ul li {
      margin: 0 10px;
      list-style: none;
      display: block;
      line-height: 30px;
      border-bottom: 1px solid #d39b33;
    }

    #searchPanel ul li.active {
      background: #fbce52;
    }

    #searchPanel ul li a {
      padding: 6px 0;
      color: #FFF;
      font-size: 14px;
      font-weight: bold;
      text-transform: uppercase;
      display: block;
      text-decoration: none;
      -webkit-transition-property: all;
      -webkit-transition-duration: 0.1s;
      -webkit-transition-timing-function: ease-out;
      -moz-transition-property: all;
      -moz-transition-duration: 0.1s;
      -moz-transition-timing-function: ease-out;
      -ms-transition-property: all;
      -ms-transition-duration: 0.1s;
      -ms-transition-timing-function: ease-out;
      -o-transition-property: all;
      -o-transition-duration: 0.1s;
      -o-transition-timing-function: ease-out;
      transition-property: all;
      transition-duration: 0.1s;
      transition-timing-function: ease-out;
    }

    #searchPanel ul li:hover {
      border-bottom: 1px solid #000;
    }

    #searchPanel ul li a:hover {
      color: #000;
    }

    .menu_icon {
      position: fixed;
      right: 0px;
      top: 0px;
      width: 30px;
      height: 18px;
      background: #082032;
      color: #ffffff;
      cursor: pointer;
      font-weight: bold;
      padding: 3px;
      z-index: 9999;
    }
    #currentTimer,#refreshTimer{
        font-weight: bold;
        font-size: 16px;
        border-radius: 4px;
        border:1px solid #ffffff;
        padding: 2px 0;
    }
    button,input[type=text],input[type=number], select,.combo_boxes {
      width: 100%;
      padding: 0px 5px !important;
      margin: 5px 0 !important;
      display: inline-block;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
      height: 20px;
      font-weight: bold;
      font-size: 16px;
      color: #000000;
      text-align: center;
      cursor: pointer;
    }
    button{
        cursor: pointer;
    }
    .indicator p{
        line-height: 20px;
    }
    
    /*=============================*/
    .inputGroup {
         background-color: #fff;
         display: block;
         margin: 3px 0;
         position: relative;
    }
     .inputGroup label {
         padding: 2px 5px;
         width: 100%;
         display: block;
         text-align: left;
         color: #3c454c;
         cursor: pointer;
         position: relative;
         z-index: 2;
         transition: color 200ms ease-in;
         overflow: hidden;
    }
     .inputGroup label:before {
         width: 5px;
         height: 5px;
         border-radius: 50%;
         content: '';
         background-color: #5562eb;
         position: absolute;
         left: 50%;
         top: 50%;
         transform: translate(-50%, -50%) scale3d(1, 1, 1);
         transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
         opacity: 0;
         z-index: -1;
    }
     .inputGroup label:after {
         width: 18px;
         height: 18px;
         content: '';
         border: 2px solid #d1d7dc;
         background-color: #fff;
         background-image: url("data:image/svg+xml,%3Csvg width='32' height='32' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M5.414 11L4 12.414l5.414 5.414L20.828 6.414 19.414 5l-10 10z' fill='%23fff' fill-rule='nonzero'/%3E%3C/svg%3E ");
         background-repeat: no-repeat;
         background-position: 0px 0px;
         border-radius: 50%;
         z-index: 2;
         position: absolute;
         right: 20px;
         top: 50%;
         transform: translateY(-50%);
         cursor: pointer;
         transition: all 200ms ease-in;
    }
     .inputGroup input:checked ~ label {
         color: #fff;
    }
     .inputGroup input:checked ~ label:before {
         transform: translate(-50%, -50%) scale3d(56, 56, 1);
         opacity: 1;
    }
     .inputGroup input:checked ~ label:after {
         background-color: #54e0c7;
         border-color: #54e0c7;
    }
     .inputGroup input {
         width: 22px;
         height: 22px;
         order: 1;
         z-index: 2;
         position: absolute;
         right: 0px;
         top: 50%;
         transform: translateY(-50%);
         cursor: pointer;
         visibility: hidden;
    }
 
    #new_style div{z-index: 1;}
    #searchPanel{z-index: 2;}
</style>    
</head>
 
<body onload="startTime();">
    <form id="displayReport">
        <div style="width:100%;" align="center">        
            <? //echo load_freeze_divs ("../../",'');  ?>                   
            <div class="layoutContainer" id="layoutContainer">               
                <!-- <div id="ticker">this is a simple scrolling text!</div> -->
               <div class="menu_icon" id="menu_icon"> Open </div>
                <div id="searchPanel">
                    <div id="searchPanelInner">
                        <p>Clock</p>
                        <div id="currentTimer" data-start="<?php echo time()-7200;?>"></div>
                        <p>Last Refresh On:</p>
                        <div id="refreshTimer"></div>
                        <p>Working Company:</p>
                        <? 
                            echo create_drop_down( "cbo_company_id", "", "select comp.id, comp.company_name from lib_company comp where comp.status_active=1 and comp.is_deleted=0 $company_cond order by company_name","id,company_name", 1, "-- Select Company --", 0, "show_list_view(this.value,'show_floor_listview','floor_container','requires/display_board_nrg_controller','');reset_floor_and_line();load_drop_down( 'requires/display_board_nrg_controller',this.value, 'load_drop_down_buyer', 'buyer_td' );",0 );
                            ?>
                        
                        <p>Buyer:</p>
                        <div id="buyer_td">
                            <? 
                            echo create_drop_down( "cbo_buyer_name", "", $blank_array,"",1, "-- Select Buyer --", "", "" );
                            ?>
                        </div>    
                        <p>Sewing Date:</p>
                        <input type="text" class="datepicker" autocomplete="off" value="<?=date('d-m-Y');?>" name="txt_sewing_date" id="txt_sewing_date">
                        <button type="button" onclick="fn_report_generated()">Show All Line</button>
                        <div type="button" id="export_btn"></div>
                        <span>Refresh Time(s) : <input type="number" name="refresh_time" id="refresh_time" value="600" style="width: 70px;"></span>
                        <!-- <p>Sewing Unit:</p> -->
                        <h3 style="padding-top:5px;" align="center" id="accordion_h1" class="accordion_h" onClick="accordion_menu( this.id,'floor_container', '')"> -Sewing Unit:</h3>
                        <div id="floor_container" style="display: none;">
                            
                        </div> 
                        <p>Sewing Line:</p>
                        <!-- <div id="sewing_line">
                            <? 
                                //echo create_drop_down( "cbo_line_id", "", $blank_array,"",1, "-- Select Line --", "", "" );
                            ?>
                        </div> -->    
                        <input type="text" readonly="true" name="txt_line" id="txt_line" placeholder="Browse" onclick="openmypage_line()">
                        <input type="hidden" name="hidden_line_id" id="hidden_line_id">
                        <p>Source</p>
                        <div id="buyer_td">
                            <? 
                            echo create_drop_down( "cbo_source", "", $knitting_source,"",1, "-- Select --", "", "","","1,2" );
                            ?>
                        </div>
                        <button type="button" id="rpt_button" onclick="fn_report_generated()">Show By Selected</button>
                        <div class="indicator">
                            <p>Multiple Style &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="background:yellow; padding:0 30px; border-radius:0px; cursor:pointer; ">&nbsp;</span></p>
                            <p>Above Targets &nbsp;&nbsp;<span style="background:blue; padding:0 30px; border-radius:0px; cursor:pointer; ">&nbsp;</span></p>
                            <p>Achive Targets &nbsp;&nbsp;<span style="background:green; padding:0 30px; border-radius:0px; cursor:pointer; ">&nbsp;</span></p>
                            <p>Bellow Targets &nbsp;&nbsp;<span style="background:red; padding:0 30px; border-radius:0px; cursor:pointer; ">&nbsp;</span></p>
                        </div>
                    </div>
                </div>
                <div style="padding: 0; margin:0;">
                    <div style="padding: 0; margin:0;"> 
                        <div style="font-size:24px;color:#FFFFFF;background:#000000;width:100%;z-index:-1;">
                            <div>
                                <div style="font-size:22px;color:#FFFFFF;text-align: center;">Hourly Sewing Production</div>
                                <div style="text-align: center;">
                                    <span id="company_name" style="color: #ffffff;font-size:18px;">Company : ,</span>
                                    <span id="floor_name" style="color: #ffffff;font-size:18px;">Unit : All Floor,</span>
                                    <span id="line_name" style="color: #ffffff;font-size:18px;">, Line : All Line,</span>
                                    <span id="prod_date" style="color: #ffffff;font-size:18px;"></span> 
                                </div>
                            </div>
                        </div>
                        <div id="rptContainer"></div>
                    </div>
                </div>
            </div>

        </div>
        
    </form>
</body>
<script>
    $(document).ready(function(){
                
        $('#searchPanel').css("right","-200px");
            $('.menu_icon').on('click',function(){
                if($('.menu_icon').hasClass('open'))
                {
                    $(this).removeClass('open');
                    $(this).text('Open');
                    $(this).animate({
                        "right":"0px",
                        "background-position":"0px"
                    });
                    $('#searchPanel').animate({"right":"-200px"});
                    // $('body').css("position","absolute");
                    $('body').animate({
                        "right":"0px",
                        "z-index":"999"
                    });
                }
                else
                {
                    $(this).addClass('open');
                    $(this).text('Close');
                    $(this).animate({
                        "right":"200px",
                        "background-position":"-40px"
                    });
                    $('#searchPanel').animate({"right":"0px"});
                    // $('body').css("position","absolute");
                    $('body').animate({
                        "right":"200px",
                        "z-index":"999"
                    });
                
                }
            });
            // ==============================
            // showClock();
            refreshTimer();
            
        });

        //get new date from timestamp in data-start attr
        // alert($("#currentTimer").attr("data-start"));
        var freshTime = new Date(parseInt($("#currentTimer").attr("data-start"))*1000);
        //loop to tick clock every second
        var displayClock = function showClock() {
            //set text of clock to show current time
            $("#currentTimer").text(freshTime.toLocaleTimeString());
            //add a second to freshtime var
            freshTime.setSeconds(freshTime.getSeconds() + 1);
            //wait for 1 second and go again
            setTimeout(showClock, 1000); 
            // ======================= set current date when day end ========================= 
            if(document.getElementById("currentTimer").innerHTML=='12:00:00 AM')
            {
                document.getElementById('txt_sewing_date').value = '<?=date('d-m-Y');?>';
            }
        };
        // displayClock();

        var ctoday = Date.parse('<?=date('D, d M Y H:i:s',time());?>');
        function loadServerTime() 
        {
            const xhttp = new XMLHttpRequest();
            xhttp.onload = function() {
                ctoday = Date.parse(this.responseText)*1;
            }
            xhttp.open("GET", "get_server_date.php");
            xhttp.send();
        }

        setInterval(function() {ctoday += 1000;},1000);        
        setInterval(function() {loadServerTime();},10000*60);

        function startTime() {
            var today = new Date(ctoday);
            var montharray = new Array("Jan","Feb","Mar","Abr","May","Jun","Jul","Agu","Sep","Oct","Nov","Des");
            var h = today.getHours();
            var ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12;
            var m = today.getMinutes();
            var s = today.getSeconds();
            h = checkTimes(h);
            m = checkTimes(m);
            s = checkTimes(s);
            // document.getElementById('currentTimer').innerHTML = checkTimes(today.getDate())+" "+montharray[today.getMonth()]+" "+today.getFullYear() + " (" + ampm +" " + h + ":" + m + ":" + s +")"; 
            
            document.getElementById('currentTimer').innerHTML = h + ":" + m + ":" + s + " " + ampm;
            setTimeout(startTime, 1000);

            // if(document.getElementById("currentTimer").innerHTML=='12:00:00 AM')
            // {
            //     document.getElementById('txt_sewing_date').value = '<?=date('d-m-Y');?>';
            // }
        }
        // startTime();
        function checkTimes(i) {
            if (i < 10) {i = "0" + i};
            return i;
        }

        function refreshTimer() 
        {
            var date = new Date;
            var refresh_time = $("#refresh_time").val()*1000;
            // var date = new Date(Date.now() - refresh_time);
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();
            // seconds = seconds - refresh_time;
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            minutes = minutes < 10 ? '0'+minutes : minutes;
            var strTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            // return strTime;
            // $("#refreshTimer").text(strTime);
            // $("#refreshTimer").text(freshTime.toLocaleTimeString());
            $("#refreshTimer").text(document.getElementById("currentTimer").innerHTML);
            // alert('ok');
        }
        refreshTimer();
        // $(".menu_icon").click();
</script>
<script src="../../includes/functions_bottom.js" type="text/javascript"></script>
</html>
