<div>
    {if $message != null}
    	<div class="alert alert-success" role="alert">
        	<p calss="alert-text">
            	{$message}
            </p>
        </div>
    {/if}

    <form action="" method="post">
    	<div class="form-group">
        	<label class="form-control-label" for="halmar-login">Login:</label>
    		<input type="text" name="login" class="form-control" id="halmar-login" value="{$login}" required></input>
        </div>
        <div class="form-group">
        	<label class="form-control-label" for="halmar-password">Password:</label>
    		<input type="text" name="password" class="form-control" id="halmar-password" value="{$password}" required></input>
        </div>
        <div class="form-group">
        	<label class="form-control-label" for="halmar-data">Data:</label>
    		<input type="text" name="data" class="form-control" id="halmar-data" value="{$data}"></input>
        </div>
		<div class="form-group">
        	<button type="submit" class="btn btn-primary" name="testLogin">test login</button>
            <button type="submit" class="btn btn-primary" name="testPostOrder">test post order</button>
        </div>
    </form>
</div>