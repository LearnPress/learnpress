<<<<<<< HEAD
<?php
ob_start();
?>
<style type="text/css">

	p{
		margin: 0 0 20px 0;
	}

</style>
<?php echo preg_replace( '!</?style.*>!', '', ob_get_clean() );
=======
<?php
ob_start();
?>
<style type="text/css">

	p{
		margin: 0 0 20px 0;
	}

</style>
<?php echo preg_replace( '!</?style.*>!', '', ob_get_clean() );
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
