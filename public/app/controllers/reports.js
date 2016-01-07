//
// Income Statement
//
app.controller('ReportsIncomeStatementCtrl', function ($scope, $http, $routeParams, $location) 
{  
  $scope.statement = [];
  $scope.date_start = $routeParams.start;
  $scope.date_end = $routeParams.end;
  
  // Date change.
  $scope.date_change = function ()
  {
    $location.path('/a/reports/income-statement').search({ start: $scope.date_start, end: $scope.date_end });
  }
  
  $http.get('/api/v1/reports/income_statement?start=' + $routeParams.start + '&end=' + $routeParams.end).success(function (json) {
    $scope.statement = json.data;
  });
});

//
// Orders
//
app.controller('ReportsOrdersCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.orders = [];
  
  // Catch Websocket event - Orders:insert
  $scope.$on('Orders:insert', function (event, args) {    
    $scope.refresh_orders();
    $scope.$apply();
  });    

  // Catch Websocket event - Orders:update
  $scope.$on('Orders:update', function (event, args) {
    $scope.refresh_orders();
    $scope.$apply();
  });
    
  // Get a list of orders
  $scope.refresh_orders = function ()
  {
    $http.get('/api/v1/orders?order=OrdersEntered&sort=desc').success(function (json) {
      $scope.orders = json.data;
    });
  }
  
  $scope.refresh_orders(); 
});

//
// Activity
//
app.controller('ReportsActivityCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.activity = [];
  
  // Catch Websocket event - Activity:insert
  $scope.$on('Activity:insert', function (event, args) {    
    $scope.refresh();
    $scope.$apply();
  });    

  // Catch Websocket event - Activity:update
  $scope.$on('Activity:update', function (event, args) {
    $scope.refresh();
    $scope.$apply();
  });
    
  // Get a list of Activity
  $scope.refresh = function ()
  {
    $http.get('/api/v1/activity?order=ActivityId&sort=desc').success(function (json) {
      $scope.activity = json.data;
    });
  }
  
  $scope.refresh(); 
});

//
// Performance
//
app.controller('ReportsPerformanceCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.marks = [];
  
  // Catch Websocket event - Assets:update
  $scope.$on('Assets:update', function (event, args) {
    $scope.refresh_marks();
    $scope.$apply();
  });    
    
  // Get a list of marks
  $scope.refresh_marks = function ()
  {
    $http.get('/api/v1/marks?order=MarksDate&sort=desc').success(function (json) {
      $scope.marks = json.data;
    });
  }
  
  $scope.refresh_marks(); 
});

//
// Tradier History
//
app.controller('ReportsTradierHistoryCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.activity = [];
    
  // Get a list of Activity
  $scope.refresh = function ()
  {
    $http.get('/api/v1/tradierhistory?order=TradierHistoryDate&sort=desc').success(function (json) {
      $scope.history = json.data;
    });
  }
  
  $scope.refresh(); 
});