<div class="span12 zone-content">
	
	<h1>Stockpeer Login</h1>
	
	<div class="row span11 well">
  	
  	<?php if(isset($failed) && $failed) : ?>
  	<div class="alert alert-error">Failed to login you in. Please try again.</div>
  	<?php endif; ?>
		
		<form role="form" action="/login" method="post">
      <input type="hidden" name="_token" value="<?=csrf_token()?>">
			
			<div class="row span10">		
  					
			  <div class="control-group">
			    <label class="control-label">Email</label>
			    <div class="controls">
            <input type="text" name="email" class="input-xlarge" placeholder="user@example.com" />
					</div>
			  </div>
			  
			  <div class="control-group">
			    <label class="control-label">Password</label>
			    <div class="controls">
            <input type="password" name="password" class="input-xlarge" />
					</div>
			  </div>			  	
			  		  
			</div>						
			
			<div class="row span10">
		  	<button type="submit" class="btn btn-primary">Login</button>
			</div>
		</form>
	</div>	

	
</div>