app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
  
  $routeProvider.

    // /a/dashboard
    when('/a/dashboard', {
      templateUrl: '/app/html/dashboard/index.html',
      controller: 'DashboardCtrl'
    }).
    
    // /a/screener/credit-spreads
    when('/a/screener/credit-spreads', {
      templateUrl: '/app/html/screener/credit-spreads.html',
      controller: 'ScreenerCreditSpreadsCtrl'
    }).
    
    // /a/accounting/assets
    when('/a/accounting/assets', {
      templateUrl: '/app/html/accounting/assets.html',
      controller: 'AccountingAssetsCtrl'
    }). 
    
    // /a/accounting/shares
    when('/a/accounting/shares', {
      templateUrl: '/app/html/accounting/shares.html',
      controller: 'AccountingSharesCtrl'
    }). 
    
    // /a/accounting/shares/add
    when('/a/accounting/shares/add', {
      templateUrl: '/app/html/accounting/shares-add-edit.html',
      controller: 'AccountingSharesAddEditCtrl',
      resolve: { action: function() { return 'add'; } }  
    }). 
    
    // /a/accounting/shares/remove
    when('/a/accounting/shares/remove', {
      templateUrl: '/app/html/accounting/shares-add-edit.html',
      controller: 'AccountingSharesAddEditCtrl',
      resolve: { action: function() { return 'remove'; } }        
    }).            

    // /a/accounting/income
    when('/a/accounting/income', {
      templateUrl: '/app/html/accounting/income.html',
      controller: 'AccountingIncomeCtrl'
    }). 

    // /a/accounting/expenses
    when('/a/accounting/expenses', {
      templateUrl: '/app/html/accounting/expenses.html',
      controller: 'AccountingExpensesCtrl'
    }). 
    
    // /a/accounting/expenses/add
    when('/a/accounting/expenses/add', {
      templateUrl: '/app/html/accounting/expenses-add-edit.html',
      controller: 'AccountingExpensesAddEditCtrl',
      resolve: { action: function() { return 'add'; } }  
    }).     

    // /a/reports/income-statement
    when('/a/reports/income-statement', {
      templateUrl: '/app/html/reports/income-statement.html',
      controller: 'ReportsIncomeStatementCtrl'
    }).

    // /a/reports/orders
    when('/a/reports/orders', {
      templateUrl: '/app/html/reports/orders.html',
      controller: 'ReportsOrdersCtrl'
    }). 
    
    // /a/reports/activity
    when('/a/reports/activity', {
      templateUrl: '/app/html/reports/activity.html',
      controller: 'ReportsActivityCtrl'
    }).     
    
    // /a/reports/performance
    when('/a/reports/performance', {
      templateUrl: '/app/html/reports/performance.html',
      controller: 'ReportsPerformanceCtrl'
    }). 
    
     // /a/reports/tradierhistory
    when('/a/reports/tradier-history', {
      templateUrl: '/app/html/reports/tradierhistory.html',
      controller: 'ReportsTradierHistoryCtrl'
    }).    
    
    // /a/backtest/option-spreads
    when('/a/backtest/option-spreads', {
      templateUrl: '/app/html/backtest/option-spreads.html',
      controller: 'BacktestOptionsSpreadsCtrl'
    }).

    // /a/backtest/option-spreads/:id
    when('/a/backtest/option-spreads/:id', {
      templateUrl: '/app/html/backtest/option-spreads-view.html',
      controller: 'BacktestOptionsSpreadsViewCtrl'
    }).
    
    // /a/trades
    when('/a/trades', {
      templateUrl: '/app/html/trades/index.html',
      controller: 'TradesCtrl'
    }).
    
    // /a/trade-groups
    when('/a/trade-groups', {
      templateUrl: '/app/html/trade-groups/index.html',
      controller: 'TradeGroupsCtrl'
    }).                    

    // /a/settings
    when('/a/settings', {
      templateUrl: '/app/html/settings/index.html',
      controller: 'SettingsCtrl'
    }). 

    otherwise({ redirectTo: '/a/dashboard' });
    
  // HTML 5 Mode
  $locationProvider.html5Mode(true);
}]);