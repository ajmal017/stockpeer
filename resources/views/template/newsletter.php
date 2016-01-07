<?php if(! Auth::check()) : ?>
<div class="row newsletter" ng-controller="NewsletterCtrl">
  <h1>Newsletter</h1>
  <p ng-hide="done">Get our latest breaking news delivered to your inbox.</p>
  
  <h3 ng-show="done">Thanks! Your subscription to our newsletter has been confirmed.</h3>
  <p ng-show="done">You will now receive our latest breaking updates delivered to your inbox.</p>
  
  <form ng-submit="subscribe()" ng-hide="done">
  	<input type="text" placeholder="Email address" ng-model="email" />
  	<a href="" class="button-link" ng-click="subscribe()" ng-bind="btn_text">Subscribe</a>
  </form>
</div>
<?php endif; ?>