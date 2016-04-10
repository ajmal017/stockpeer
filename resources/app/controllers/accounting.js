//
// Assets
//
app.controller('AccountingAssetsCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.assets = [];
  
  // Catch Websocket event - Assets:update
  $scope.$on('Assets:update', function (event, args) {
    $scope.refresh_assets();
    $scope.$apply();
  });  
  
  // Return a total of all assets
  $scope.total = function ()
  {
    var total = 0;
    
    for(var i in $scope.assets)
    {
      total = total + parseFloat($scope.assets[i].AssetsValue);
    }
    
    return total;
  }
  
  // Save a mark
  $scope.mark_save = function (row)
  {
    row.asset_mark = false;
    
    // Send update to the API.
    $http.post('/api/v1/assets/update/' + row.AssetsId, { AssetsValue: row.AssetsValue }).success(function (json) {
      // Websocket will do that updating.   
    });
  }
  
  // Get a list of assets
  $scope.refresh_assets = function ()
  {
    $http.get('/api/v1/assets?order=AssetsName&sort=asc').success(function (json) {
      $scope.assets = json.data;
    });
  }
  
  $scope.refresh_assets(); 
});

//
// Shares
//
app.controller('AccountingSharesCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.shares = [];
  
  // Get a list of shares
  $scope.refresh_shares = function ()
  {
    $http.get('/api/v1/shares?order=SharesDate&sort=desc').success(function (json) {
      $scope.shares = json.data;
    });
  }
  
  $scope.refresh_shares(); 
});

//
// Shares - Add / Edit.
//
app.controller('AccountingSharesAddEditCtrl', function ($scope, $http, $location, action) 
{
  $scope.$parent.tab = 'accounting'; 
 
  $scope.action = action;
  
  if($scope.action == 'add')
  {
    $scope.submit_btn = 'Add Shares';
  } else
  {
    $scope.submit_btn = 'Remove Shares';    
  }
  
  $scope.fields = {
    SharesDate: new Date(),
    SharesPrice: '',
    SharesNote: ''
  }
  
  // Submit the request.
  $scope.submit = function ()
  {    
    var error = false;
    
    if($scope.fields.SharesPrice)
    {
      $scope.fields.SharesPrice_error = '';
    } else
    {
      error = true;
      $scope.fields.SharesPrice_error = 'An amount is required.';      
    }
    
    if(error)
    {
      return false;
    }

    // Make negative if shares are to be removed.
    if($scope.action == 'remove')
    {
      $scope.fields.SharesPrice = $scope.fields.SharesPrice * -1;
    }
    
    // Send request to server
    $http.post('/api/v1/shares/create', $scope.fields).success(function (json) {
      $location.path('/a/accounting/shares');
    });
    
  }

});

//
// Income
//
app.controller('AccountingIncomeCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.income = [];
  
  // Get a list of income
  $scope.refresh = function ()
  {
    $http.get('/api/v1/income?order=IncomeDate&sort=desc').success(function (json) {
      $scope.income = json.data;
    });
  }
  
  $scope.refresh(); 
});

//
// Expenses
//
app.controller('AccountingExpensesCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.expenses = [];
  
  // Get a list of income
  $scope.refresh = function ()
  {
    $http.get('/api/v1/expenses?order=ExpensesDate&sort=desc').success(function (json) {
      $scope.expenses = json.data;
    });
  }
  
  $scope.refresh(); 
});

//
// Expenses - Add / Edit.
//
app.controller('AccountingExpensesAddEditCtrl', function ($scope, $http, $location, action) 
{
  $scope.$parent.tab = 'accounting'; 
 
  $scope.action = action;
  $scope.vendors = [];
  $scope.categories = [];
  $scope.submit_btn = 'Add Expense';
  
  $scope.fields = {
    ExpensesDate: new Date(),
    ExpensesVendor: '',
    ExpensesCategory: '',
    ExpensesAmount: '',
    ExpensesNote: ''
  }
  
  // Get categories and vendors
  $scope.get_categories_vendors = function () 
  {
    // Get categories
    $http.get('/api/v1/expenses/get_categories').success(function (json) {
      $scope.categories = json.data;
      $scope.fields.ExpensesCategory = $scope.categories[0];
    });
    
    // Get Vendors
    $http.get('/api/v1/expenses/get_vendors').success(function (json) {
      $scope.vendors = json.data;
      $scope.fields.ExpensesVendor = $scope.vendors[0];
    });    
  }
  
  // Submit the request.
  $scope.submit = function ()
  {    
    var error = false;
    
    if($scope.fields.ExpensesAmount)
    {
      $scope.fields.ExpensesAmount_error = '';
    } else
    {
      error = true;
      $scope.fields.ExpensesAmount_error = 'An amount is required.';      
    }
    
    if(error)
    {
      return false;
    }
    
    console.log($scope.fields);
    
    
    // Send request to server
    $http.post('/api/v1/expenses/create', $scope.fields).success(function (json) {
      $location.path('/a/accounting/expenses');
    });
    
  }
  
  // Load data.
  $scope.get_categories_vendors();
});