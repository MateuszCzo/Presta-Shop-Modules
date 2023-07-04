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
        	<label class="form-control-label" for="order-id">order id:</label>
    		<input type="text" name="orderId" class="form-control" id="order-id" value="{$orderId}" required></input>
        </div>

        <div class="form-group">
        	<label class="form-control-label" for="order-status">order status:</label>
    		<input type="text" name="orderStatus" class="form-control" id="order-status" value="{$orderStatus}" required></input>
        </div>

		<div class="form-group">
        	<button type="submit" class="btn btn-primary" name="addOrder">add new order</button>
            <button type="submit" class="btn btn-primary" name="getStatus">get order status</button>
            <button type="submit" class="btn btn-primary" name="sendMail">send mail</button>
        </div>
    </form>
</div>