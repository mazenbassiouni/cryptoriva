<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Redirecting</title>
<style type="text/css">
*{ padding: 0; margin: 0; }
body{ background: #fff; font-family: Tahoma; color: #333; font-size: 16px; }
.system-message{ padding: 24px 48px; }
.system-message h1{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
.system-message .jump{ padding-top: 10px}
.system-message .jump a{ color: #333;}
.system-message .success,.system-message .error{ line-height: 1.8em; font-size: 36px }
.system-message .detail{ font-size: 12px; line-height: 20px; margin-top: 12px; display:none}
</style>
<style>
*{
	maring: 0;
	padding: 0;
}
body{
	font-family: 'Source Sans Pro', sans-serif;
	background: #3f6eb3;
	color: #1f3759;
}
a:link{
	color: #1f3759;
	text-decoration: none;
}
a:active{
	color: #1f3759;
	text-decoration: none;
}
a:hover{
	color: #9fb7d9;
	text-decoration: none;
}
a:visited{
	color: #1f3759;
	text-decoration: none;
}

a.underline, .underline{
	text-decoration: underline;
}

.bree-font{
	font-family: 'Bree Serif', serif;
}

#content{
	margin: 0 auto;
	width: 960px;
}

.clearfix:after {
	content: ".";
	display: block;
	clear: both;
	visibility: hidden;
	line-height: 0;
	height: 0;
}
 
.clearfix {
	display: block;
}

#logo {
	margin: 1em;
	float: left;
	display: block;
}
nav{
	float: right;
	display: block;
}
nav ul > li{
	list-style: none;
	float: left;
	margin: 0 2em;
	display: block;
}

#main-body{
	text-align: center;
}

.enormous-font{
	font-size: 10em;
	margin-bottom: 0em;
}
.big-font{
	font-size: 2em;
}
hr{
	width: 25%;
	height: 1px;
	background: #1f3759;
	border: 0px;
}



</style>
</head>
<body>
<div class="system-message">

  <div id="content">
        
        <div class="clearfix"></div>
        
        <div id="main-body">
			<?php if(isset($message)) {?>
<p class="enormous-font bree-font">Success!!</p>
<p class="big-font"> <?php echo($message); ?></p>
<?php }else{?>
<p class="enormous-font bree-font">Uhno..</p>
<p class="error"><?php echo($error); ?></p>
<?php }?>

            <br/><br/><br/>
            <p class="big-font"> Redirecting to  <a id="href" href="<?php echo($jumpUrl); ?>"> <a href="../" class="underline">Previous page</a> in [<b id="wait"><?php echo($waitSecond); ?></b>secs]</p>
        </div>
    </div>
</div>
<script type="text/javascript">

(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time <= 0) {
		location.href = href;
		clearInterval(interval);
	};
}, 0);
})();

</script>

</body>
</html>