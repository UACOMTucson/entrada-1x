<!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7 ]> <html class="no-js ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]>    <html class="no-js ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]>    <html class="no-js ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
	<meta charset="{$site_default_charset}">
	 <!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame
       Remove this if you use the .htaccess  -->
	  <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> // Remove the comment for this line if you don't care too much about validation --> 

	<title>{$page_title}</title>
	<meta name="description" content="{$page_description}" />
	<meta name="keywords" content="{$page_keywords}" />

	<meta name="robots" content="index, follow" />
	<link href="{$sys_website_url}/css/jquery/jquery-ui.css" rel="stylesheet" type="text/css" />
	
	<link href="http://localhost/entrada/www-root/css/jquery/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="{$sys_website_url}/javascript/jquery/jquery.min.js"></script>
	<script type="text/javascript" src="{$sys_website_url}/javascript/jquery/jquery-ui.min.js"></script>
	<script type="text/javascript">jQuery.noConflict();</script>
	<script src="{$template_relative}/js/script.js"></script>
	<script src="{$template_relative}/js/libs/modernizr-1.7.min.js"></script>
	{$page_head}
	<link rel="stylesheet" href="{$template_relative}/css/ie.css">
	<link rel="stylesheet" href="{$template_relative}/css/screen.css">
</head>
<body>
	{$sys_system_navigator}
    <header class="page-header">
		<div class="container">
		<div class="span-24 page-header-title">
			<hgroup class="span-6 left-space">
						<h1>School <em>of</em> Medicine</h1>
						<h2>Faculty <em>of</em> Health Sciences</h2>
			</hgroup>
			<hgroup class="span-10"">
				<h3 class="module-name prepend-1" >{$site_community_title}</h3>
			</hgroup>
		</div> <!-- ./end page-header-title -->
		</div> <!-- end container -->
		<nav class="top-navigation">
			<div class="container">
				{include file="navigation_primary.tpl" site_primary_navigation=$site_primary_navigation}
			</div><!-- end container -->
		</nav>
		<nav class="breadcrumb">
			<div class="container">
				{$site_breadcrumb_trail}
			</div>
		</nav>
		
    </header>
	 <div class="container">
		<div id="main" role="main" class="span-24">
		<p class="span-24 toggle"><a href="#" class="toggle-panel"></a></p>
		{if $show_tertiary_sideblock}
		<aside class="span-5 left-nav">
			{include file="sidebar-blocks/tertiary_block.tpl"}
		</aside>
		{/if}
		<section class="span-18 content">
			{$page_content}
		</section>
		<aside class="span-5 last right-nav collapsed">
			{if $is_logged_in && $user_is_admin}
				{include file="sidebar-blocks/admin_block.tpl"}
			{/if}
			{include file="sidebar-blocks/entrada_block.tpl"}
			{if $is_logged_in && $user_is_member}
				{include file="sidebar-blocks/community_block.tpl"}
			{/if}
			</aside>
		</div>
	
    <footer class="span-24">
		<p>{php}echo COPYRIGHT_STRING;{/php}</p>
    </footer>
  </div> <!--! end of #container -->
</body>
	
</html>