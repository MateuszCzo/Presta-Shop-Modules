<div>
	{if $message != null}
    	<div class="alert alert-success" role="alert">
        	<p calss="alert-text">
            	{$message}
            </p>
        </div>
    {else}
    
    {/if}

	<form action="" method="post">
    	<div class="form-group">
        	<label class="form-control-label" for="input">Bot Press id:</label>
    		<input type="text" name="botPressId" class="form-control" id="input" value="{$botPressId}" required></input>
        </div>
		<div class="form-group">
        	<button type="submit" class="btn btn-primary">Zapisz</button>
        </div>
    </form>
</div>