<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>ga-lib</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/ga-lib.css">
    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>
  <body class="accounts-open profiles-open">
    <div id="account-nav" class="nav-menu">
      <form>
        <input type="text" class="input-medium search-query" placeholder="Search">
        <i class="icon-search icon-white"></i>
      </form>
      <ul></ul>
      <div class="topbar">
        <button type="button" data-toggle="accounts" class="pull-left btn btn-inverse"><i class="icon-cog icon-white"></i></button>
        <span>Accounts</span>
      </div>
    </div>
    <div id="profile-nav" class="nav-menu" data-dismiss="accounts">
      <form>
        <input type="text" class="input-medium search-query" placeholder="Search">
        <i class="icon-search icon-white"></i>
      </form>
      <ul></ul>
      <div class="topbar">
        <button type="button" data-toggle="accounts" class="pull-left btn btn-inverse"><i class="icon-user icon-white"></i></button>
        <span>Profiles</span>
      </div>
    </div>
    <div id="profiles" class="profiles-con" data-dismiss="profiles">
      <button class="btn btn-profiles" data-toggle="profiles"><i class="icon-th-list"></i></button>
      <ul></ul>
    </div>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="/js/bootstrap.js"></script>
    <script src="/js/underscore.js"></script>
    <script src="/js/backbone.js"></script>
    <script src="/js/hammer.js"></script>
    <script src="/js/jquery.hammer.js"></script>
    <script src="/js/ga-lib.js"></script>
  </body>
</html>