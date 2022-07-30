<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $email_heading ) ) {
	return;
}
?>
<!DOCTYPE html>
<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="
0">
<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
		<tr>
			<td align="center" valign="top">
				<?php if ( isset( $image_header ) ) { ?>
					<div id="template_header_image">
						<p style="margin-top:0;">
							<img src="<?php echo esc_url_raw( $image_header ); ?>"
								alt="<?php echo get_bloginfo( 'name', 'display' ); ?>"/>
						</p>
					</div>
				<?php } ?>
				<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
					<thead id="email-header">
					<tr>
						<td align="center" valign="top">
							<h2 class="order-heading">
								<?php echo wp_kses_post( $email_heading ); ?>
							</h2>
						</td>
					</tr>
					</thead>
					<tbody id="email-body">
					<tr>
						<td align="center" valign="top">
