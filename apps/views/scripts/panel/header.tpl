<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td><img id="logo" src="<?php echo burl(); ?>/images/customer_logo.jpg" /></td>
		<td align="right"><ul class="loginfo">
                <li><form class="button_link" action="<?php echo burl()?>/panel/user/sub/pwdchange">
                <input type="submit" value="Change Password" /></form>&nbsp;
                <form class="button_link" action="<?php echo burl()?>/user/logout">
                <input type="submit" value="Logout" /></form><br>
				<li><b><?php echo $this->identity;  ?></b> is currently logged in 
			</ul></td>
	</tr>
</table>
<div id="menu">
<?php 
    echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    if(isAllow('dashboard','read')) {
        echo 
        "<ul>
             <li>
             <h2><a href='" . burl() . "/panel/dashboard'>Dashboard</a></h2>
             </li>
         </ul>";
        echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    }
    if(isAllow('asset','read')) {
        echo 
        "<ul>
             <li>
             <h2><a href='" . burl() . "/panel/asset/sub/searchbox/s/search'>Assets</a></h2>
             </li>
         </ul>";
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(isAllow('finding','read')) {
        echo 
        '<ul>
            <li> 
            <h2><a>Finding</a></h2>
            <ul>';
        if(isAllow('finding','create')) {
            echo "\n",'<li><a href="'.burl().'/panel/finding/sub/create">New Finding</a> </li>
                <li><a href="'.burl().'/finding/injection">Spreadsheet Upload</a> </li>
                <li><a href="'.burl().'/finding/import">Upload Scan Results</a> </li> ';
        }
        echo '</ul>
             </li></ul>';
        echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    }
    if(isAllow('remediation','read')) {
        echo '<ul><li>
              <h2><a href="'.burl().'/panel/remediation/sub/index/">Remediation</a></h2>
              <ul>
              <li><a href="'.burl().'/panel/remediation/sub/summary">Remediation Summary</a></li>
              <li><a href="'.burl().'/panel/remediation/sub/searchbox">Remediation Search</a></li>
              </ul>
              </li></ul>';
        echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    }
    if(isAllow('report','read')) { 
        echo "\n",'<ul><li><h2><a>Reports</a></h2>
              <ul>';
        if(isAllow('report', 'generate_poam_report' )) {
            echo "\n",'<li><a href="'.burl().'/panel/report/sub/poam">POA&amp;M Report</a></li>';
        }            
        if(isAllow('report','generate_fisma_report')) {
            echo "\n",'<li><a href="'.burl().'/panel/report/sub/fisma">FISMA POA&amp;M Report</a></li>';
        }
        if(isAllow('report','generate_general_report')) {
            echo "\n",'<li><a href="'.burl().'/panel/report/sub/general">General Report</a></li>';
        }
        if(isAllow('report','generate_system_rafs')) {
            echo "\n",'<li><a href="'.burl().'/panel/report/sub/rafs">Generate System RAFs</a></li>';
        }
        if(isAllow('report','generate_overdue_report')) {
            echo "\n",'<li><a href="'.burl().'/panel/report/sub/overdue">Overdue Report</a></li>';
        }            
        echo'</ul>
             </li></ul>';
        echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    }
    if(isAllow('admin','read')) {
        echo'<ul><li><h2><a>Administration</a></h2>';
        echo'<ul>';
        if(isAllow('admin_users','read')) {
            echo'<li><a href="'.burl().'/panel/account/sub/list">Users</a></li>';
        }
        if(isAllow('admin_roles','read')){
            echo'<li><a href="'.burl().'/panel/role/sub/list">Roles</a></li>';
        }
        if(isAllow('admin_systems','read')) {
            echo'<li><a href="'.burl().'/panel/system/sub/list">Systems</a></li>';
        }
        if(isAllow('admin_products','read')) {
            echo'<li><a href="'.burl().'/panel/product/sub/list">Products</a></li>';
        }
        if(isAllow('admin_system_groups','read')) {
            echo'<li><a href="'.burl().'/panel/sysGroup/sub/list">System Group</a></li>';
        }
        if(isAllow('admin_sources','read')) {
            echo'<li><a href="'.burl().'/panel/source/sub/list">Finding Sources</a></li>';
        }
        if(isAllow('admin_networks','read')){
            echo'<li><a href="'.burl().'/panel/network/sub/list">Networks</a>';
        }
        if(isAllow('app_configuration','update')) {
            echo'<li><a href="'.burl().'/panel/config">Configuration</a></li>';
        }
        echo'</ul>
            </li></ul>';
        echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    }
    /*
    if(isAllow('vulnerability','read')) {
        echo'<ul><li><h2><a href="/mainPanel.php?panel=association" >Vulnerability</a></h2>';
        echo'<ul><li><a href="#">Asset Dashboard</a></li>';
        if(isAllow('vulnerability','create')) {
            echo'<li><a href="#">Create an Asset</a></li>';
        }
        echo'</ul>
        </li></ul>';
        echo '<img src="' . burl() . '/images/menu_line.gif" border="0">';
    }*/
?>
&nbsp;
</div>
<div id="msgbar"></div>
