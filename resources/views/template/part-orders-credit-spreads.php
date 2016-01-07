<div class="well" ng-show="preview_credit_spreads">
  <h3 ng-show="preview_credit_spreads_data.action == 'open'">Open Credit Spread - Preview Order</h3>
  <h3 ng-show="preview_credit_spreads_data.action == 'close'">Close Credit Spread - Preview Order</h3>
  
  <table class="table">
    <tr>
      <td>Buy Leg</td>
      <td ng-bind="preview_credit_spreads_data.buy_leg"></td>
    </tr>

    <tr>
      <td>Sell Leg</td>
      <td ng-bind="preview_credit_spreads_data.sell_leg"></td>
    </tr>

    <tr>
      <td>Status</td>
      <td><span ng-bind="preview_credit_spreads_data.status"></td>
    </tr>

    <tr>
      <td>Lots</td>
      <td ng-bind="preview_credit_spreads_data.lots"></td>
    </tr>

    <tr ng-show="preview_credit_spreads_data.action == 'open'">
      <td>Credit</td>
      <td>$<span ng-bind="(preview_credit_spreads_data.price * -1) | number:2"></span></td>
    </tr>

    <tr ng-show="preview_credit_spreads_data.action == 'close'">
      <td>Debit</td>
      <td>$<span ng-bind="preview_credit_spreads_data.price | number:2"></span></td>
    </tr>

    <tr ng-show="preview_credit_spreads_data.action == 'open'">
      <td>Margin</td>
      <td>$<span ng-bind="preview_credit_spreads_data.option_requirement | number:2"></span></td>
    </tr>

    <tr ng-show="preview_credit_spreads_data.action == 'open'">
      <td>Total Credit</td>
      <td>$<span ng-bind="((preview_credit_spreads_data.price * -1) * 100 * preview_credit_spreads_data.lots) | number:2"></span></td>
    </tr>

    <tr ng-show="preview_credit_spreads_data.action == 'close'">
      <td>Total Debit</td>
      <td>$<span ng-bind="(preview_credit_spreads_data.price * 100 * preview_credit_spreads_data.lots) | number:2"></span></td>
    </tr>

    <tr>
      <td>Commission</td>
      <td>$<span ng-bind="preview_credit_spreads_data.commission | number:2"></span></td>
    </tr>
  </table>
  
  <a href="" class="btn btn-primary" ng-click="submit_order()" ng-bind="preview_credit_submit_btn"></a> or
  <a href="" ng-click="order_cancel()" class="red">Cancel</a>
	
</div>