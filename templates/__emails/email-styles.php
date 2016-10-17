<?php
ob_start();
?>
<style type="text/css">

	p{
		margin: 0 0 20px 0;
	}

</style>
<?php echo preg_replace( '!</?style.*>!', '', ob_get_clean() );
