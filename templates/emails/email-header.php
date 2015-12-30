<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!DOCTYPE html>
<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
		<tr>
			<td align="left" valign="top">
				<div id="template_header_image">
					<?php
					if ( $img = LP()->settings->get( 'emails_general.header_image' ) ) {
						echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . get_bloginfo( 'name', 'display' ) . '" /></p>';
					}
					?>
				</div>
				<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
					<tr>
						<td align="left" valign="top">
							<h1><?php echo $email_heading; ?></h1>
						</td>
					</tr>
					<tr>
						<td align="left" valign="top">